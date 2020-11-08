<?php

namespace TeamTeaTime\Forum\Http\Requests;

use TeamTeaTime\Forum\Events\UserRenamedThread;
use TeamTeaTime\Forum\Interfaces\FulfillableRequest;

class RenameThread extends BaseRequest implements FulfillableRequest
{
    public function authorize(): bool
    {
        $thread = $this->route('thread');
        return $this->user()->can('rename', $thread);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:' . config('forum.general.validation.title_min')]
        ];
    }

    public function fulfill()
    {
        $thread = $this->route('thread');
        $thread->title = $this->input('title');
        $thread->save();

        event(new UserRenamedThread($this->user(), $thread));

        return $thread;
    }
}
