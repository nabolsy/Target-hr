<?php

namespace App\Listeners;

use App\Events\AssetAssigned;
use App\Events\NotificationSent;
use App\Models\NotificationLog;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Notify the employee when an asset is assigned to them.
 *
 * Same shape as SendTaskAssignedNotification — body summarises the
 * asset (name + category + serial when present); skips when the
 * employee has no linked user account.
 */
class SendAssetAssignedNotification
{
    public function handle(AssetAssigned $event): void
    {
        $asset = $event->asset;
        $employee = $event->employee;
        $userId = $employee?->user_id;

        if (! $userId) {
            return;
        }

        $title = 'An asset was assigned to you';
        $body = sprintf(
            '%s · %s%s',
            $asset->name,
            $asset->category?->label() ?? $asset->category?->value ?? 'asset',
            $asset->serial_number ? ' · '.$asset->serial_number : '',
        );

        $log = NotificationLog::create([
            'company_id' => $asset->company_id,
            'user_id' => $userId,
            'type' => 'system',
            'title' => $title,
            'body' => $body,
            'data' => [
                'type' => 'system',
                'title' => $title,
                'message' => $body,
                'asset_id' => $asset->id,
                'asset_name' => $asset->name,
                'asset_code' => $asset->asset_code,
                'category' => $asset->category?->value,
                'serial_number' => $asset->serial_number,
                'url' => '/assets',
            ],
        ]);

        try {
            broadcast(new NotificationSent($log));
        } catch (Throwable $e) {
            Log::warning('AssetAssigned broadcast failed', [
                'user_id' => $userId,
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
