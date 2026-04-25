<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskAttachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TaskAttachmentController extends Controller
{
    private const DISK = 'public';

    public function index(Task $task): JsonResponse
    {
        // View attachments ⟺ view the parent task.
        $this->authorize('view', $task);

        $task->load(['attachments.uploader:id,name']);

        return response()->json([
            'data' => $task->attachments->map(fn (TaskAttachment $a) => $this->format($a))->values(),
        ]);
    }

    public function store(Request $request, Task $task): JsonResponse
    {
        // Uploading an attachment is a task write — use the update gate.
        $this->authorize('update', $task);

        $request->validate([
            'file' => 'required|file|max:20480', // 20 MB
        ]);

        $file = $request->file('file');
        $path = $file->store("task-attachments/{$task->id}", self::DISK);

        $attachment = $task->attachments()->create([
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'uploaded_by' => $request->user()->id,
        ]);

        $attachment->load('uploader:id,name');

        return response()->json([
            'data' => $this->format($attachment),
            'message' => 'Attachment uploaded.',
        ], 201);
    }

    public function destroy(Task $task, TaskAttachment $attachment): JsonResponse
    {
        $this->authorize('update', $task);

        if ($attachment->task_id !== $task->id) {
            return response()->json(['message' => 'Attachment does not belong to this task.'], 404);
        }

        Storage::disk(self::DISK)->delete($attachment->file_path);
        $attachment->delete();

        return response()->json(['message' => 'Attachment deleted.']);
    }

    public function download(Task $task, TaskAttachment $attachment): BinaryFileResponse
    {
        // Downloading is a read — any viewer of the task can download.
        $this->authorize('view', $task);

        abort_unless($attachment->task_id === $task->id, 404);

        return response()->download(
            Storage::disk(self::DISK)->path($attachment->file_path),
            $attachment->file_name
        );
    }

    private function format(TaskAttachment $a): array
    {
        return [
            'id' => $a->id,
            'task_id' => $a->task_id,
            'file_name' => $a->file_name,
            'file_size' => $a->file_size,
            'mime_type' => $a->mime_type,
            'url' => Storage::disk(self::DISK)->url($a->file_path),
            'uploaded_by' => $a->uploaded_by,
            'uploader' => $a->uploader ? ['id' => $a->uploader->id, 'name' => $a->uploader->name] : null,
            'created_at' => $a->created_at?->toISOString(),
        ];
    }
}
