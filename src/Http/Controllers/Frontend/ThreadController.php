<?php namespace TeamTeaTime\Forum\Http\Controllers\Frontend;

use Forum;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use TeamTeaTime\Forum\Events\UserCreatingThread;
use TeamTeaTime\Forum\Events\UserMarkingNew;
use TeamTeaTime\Forum\Events\UserViewingNew;
use TeamTeaTime\Forum\Events\UserViewingThread;
use TeamTeaTime\Forum\Http\Requests\DestroyThread;
use TeamTeaTime\Forum\Http\Requests\LockThread;
use TeamTeaTime\Forum\Http\Requests\MoveThread;
use TeamTeaTime\Forum\Http\Requests\PinThread;
use TeamTeaTime\Forum\Http\Requests\RenameThread;
use TeamTeaTime\Forum\Http\Requests\StoreThread;
use TeamTeaTime\Forum\Http\Requests\UnlockThread;
use TeamTeaTime\Forum\Http\Requests\UnpinThread;
use TeamTeaTime\Forum\Models\Category;
use TeamTeaTime\Forum\Models\Thread;

class ThreadController extends BaseController
{
    public function recent(Request $request): View
    {
        $threads = Thread::recent();

        if ($request->has('category_id'))
        {
            $threads = $threads->where('category_id', $request->input('category_id'));
        }

        $threads = $threads->get();

        // Filter the threads according to the user's permissions
        $threads = $threads->filter(function ($thread)
        {
            return (! $thread->category->private || $request->user() != null && $request->user()->can('view', $thread->category));
        });

        event(new UserViewingNew($threads));

        return view('forum::thread.recent', compact('threads'));
    }

    public function unread(Request $request): View
    {
        $threads = Thread::recent();

        if ($request->has('category_id'))
        {
            $threads = $threads->where('category_id', $request->input('category_id'));
        }

        $threads = $threads->get()->filter(function ($thread)
        {
            return $thread->userReadStatus != false
                && (! $thread->category->private || $request->user()->can('view', $thread->category));
        });

        event(new UserViewingNew($threads));

        return view('forum::thread.unread', compact('threads'));
    }

    public function markRead(Request $request): RedirectResponse
    {
        $threads = $this->api('thread.mark-new')->parameters($request->only('category_id'))->patch();

        event(new UserMarkingNew);

        if ($request->has('category_id'))
        {
            $category = $this->api('category.fetch', $request->input('category_id'))->get();

            if ($category)
            {
                Forum::alert('success', 'categories.marked_read', 0, ['category' => $category->title]);
                return redirect(Forum::route('category.show', $category));
            }
        }

        Forum::alert('success', 'threads.marked_read');
        return redirect(config('forum.routing.prefix'));
    }

    public function show(Request $request, Thread $thread): View
    {
        event(new UserViewingThread($thread));

        $category = $thread->category;

        $categories = $request->user() && $request->user()->can('moveThreadsFrom', $category)
                    ? Category::acceptsThreads()->get()->toTree()
                    : [];

        $posts = $thread->postsPaginated;

        return view('forum::thread.show', compact('categories', 'category', 'thread', 'posts'));
    }

    public function create(Request $request, Category $category)
    {
        if (! $category->accepts_threads)
        {
            Forum::alert('warning', 'categories.threads_disabled');

            return redirect(Forum::route('category.show', $category));
        }

        event(new UserCreatingThread($category));

        return view('forum::thread.create', compact('category'));
    }

    public function store(StoreThread $request, Category $category)
    {
        if (! $category->accepts_threads)
        {
            Forum::alert('warning', 'categories.threads_disabled');

            return redirect(Forum::route('category.show', $category));
        }
        
        $thread = $request->fulfill();

        Forum::alert('success', 'threads.created');

        return redirect(Forum::route('thread.show', $thread));
    }

    public function lock(LockThread $request): RedirectResponse
    {
        $thread = $request->fulfill();

        Forum::alert('success', 'threads.updated', 1);

        return redirect(Forum::route('thread.show', $thread));
    }

    public function unlock(UnlockThread $request): RedirectResponse
    {
        $thread = $request->fulfill();

        Forum::alert('success', 'threads.updated', 1);

        return redirect(Forum::route('thread.show', $thread));
    }

    public function pin(PinThread $request): RedirectResponse
    {
        $thread = $request->fulfill();

        Forum::alert('success', 'threads.updated', 1);

        return redirect(Forum::route('thread.show', $thread));
    }

    public function unpin(UnpinThread $request): RedirectResponse
    {
        $thread = $request->fulfill();

        Forum::alert('success', 'threads.updated', 1);

        return redirect(Forum::route('thread.show', $thread));
    }
    
    public function rename(RenameThread $request): RedirectResponse
    {
        $thread = $request->fulfill();

        Forum::alert('success', 'threads.updated', 1);

        return redirect(Forum::route('thread.show', $thread));
    }

    public function move(MoveThread $request): RedirectResponse
    {
        $thread = $request->fulfill();

        Forum::alert('success', 'threads.updated', 1);

        return redirect(Forum::route('thread.show', $thread));
    }

    public function destroy(DestroyThread $request): RedirectResponse
    {
        $thread = $request->fulfill();

        Forum::alert('success', 'threads.deleted', 1);

        return redirect(Forum::route('category.show', $thread->category));
    }

    /**
     * DELETE: Delete threads in bulk.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function bulkDestroy(Request $request)
    {
        $this->validate($request, ['action' => 'in:delete,permadelete']);

        $parameters = $request->all();

        $parameters['force'] = 0;
        if (!config('forum.preferences.soft_deletes') || ($request->input('action') == 'permadelete')) {
            $parameters['force'] = 1;
        }

        $threads = $this->api('bulk.thread.delete')->parameters($parameters)->delete();

        return $this->bulkActionResponse($threads, 'threads.deleted');
    }

    /**
     * PATCH: Update threads in bulk.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function bulkUpdate(Request $request)
    {
        $this->validate($request, ['action' => 'in:restore,move,pin,unpin,lock,unlock']);

        $action = $request->input('action');

        $threads = $this->api("bulk.thread.{$action}")->parameters($request->all())->patch();

        return $this->bulkActionResponse($threads, 'threads.updated');
    }
}