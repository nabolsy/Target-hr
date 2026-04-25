<?php

namespace App\Listeners;

use App\Events\DocumentUploaded;
use App\Events\NotificationSent;
use App\Models\Employee;
use App\Models\NotificationLog;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Notify the employee whose document was just uploaded.
 *
 * Skips when:
 *   - the document isn't tied to an employee (system-wide doc), or
 *   - the employee has no linked user account.
 *
 * Skips when the uploader IS the document subject (e.g. an employee
 * uploads their own CV) — no point in notifying yourself about your
 * own action.
 */
class SendDocumentUploadedNotification
{
    public function handle(DocumentUploaded $event): void
    {
        $doc = $event->document->loadMissing(['uploader']);

        // Look up Employee directly from `employee_id`. The
        // EmployeeDocument::employee belongsTo points at the User model
        // (legacy schema quirk), so going through the relation gives us
        // the wrong row. Fetch the Employee explicitly.
        $employee = $doc->employee_id
            ? Employee::find($doc->employee_id)
            : null;
        $userId = $employee?->user_id;

        if (! $userId) {
            return;
        }
        if ($doc->uploader_id && (int) $doc->uploader_id === (int) $userId) {
            return;
        }

        // type is an enum cast — coerce to its primitive value (or fall
        // back to the file type label) before string-formatting.
        $typeLabel = $doc->type instanceof \BackedEnum
            ? $doc->type->value
            : (is_string($doc->type) ? $doc->type : 'document');

        $title = 'A new document was added to your file';
        $body = sprintf(
            '%s · %s%s',
            $doc->title ?: $doc->file_name,
            $typeLabel,
            $doc->expiry_date ? ' · expires '.optional($doc->expiry_date)->format('M j, Y') : '',
        );

        $log = NotificationLog::create([
            'company_id' => $doc->company_id,
            'user_id' => $userId,
            'type' => 'document',
            'title' => $title,
            'body' => $body,
            'data' => [
                'type' => 'document',
                'title' => $title,
                'message' => $body,
                'document_id' => $doc->id,
                'document_title' => $doc->title,
                'document_type' => $typeLabel,
                'uploader_name' => $doc->uploader?->name,
                'expiry_date' => optional($doc->expiry_date)->toDateString(),
                'url' => '/documents',
            ],
        ]);

        try {
            broadcast(new NotificationSent($log));
        } catch (Throwable $e) {
            Log::warning('DocumentUploaded broadcast failed', [
                'user_id' => $userId,
                'document_id' => $doc->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
