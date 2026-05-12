<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskCommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $isOwner = $user && (int) $user->id === (int) $this->user_id;

        return [
            'id'         => $this->id,
            'task_id'    => $this->task_id,
            'parent_id'  => $this->parent_id,
            'user'       => new UserResource($this->whenLoaded('user')),
            'body'       => $this->body,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'can_delete' => $isOwner,
            'replies'    => self::collection($this->whenLoaded('replies')),
        ];
    }
}
