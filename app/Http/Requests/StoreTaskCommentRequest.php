<?php

namespace App\Http\Requests;

use App\Models\TaskComment;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreTaskCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body'      => ['required', 'string', 'max:5000'],
            'parent_id' => ['nullable', 'integer', 'exists:task_comments,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $parentId = $this->input('parent_id');
            if (! $parentId) {
                return;
            }
            $parent = TaskComment::find($parentId);
            if (! $parent) {
                return;
            }
            // A reply must belong to the same task as the parent.
            if ((int) $parent->task_id !== (int) $this->route('task')?->id) {
                $v->errors()->add('parent_id', 'Reply must belong to the same task.');
            }
            // Only one level of nesting — no replies to replies.
            if ($parent->parent_id !== null) {
                $v->errors()->add('parent_id', 'Cannot reply to a reply.');
            }
        });
    }
}
