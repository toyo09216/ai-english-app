<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use App\Http\Requests\StoreThreadRequest;
use App\Http\Requests\UpdateThreadRequest;
use App\Models\Thread;
use App\Models\Message;

class ThreadController extends Controller
{
    /**
     * トップ画面表示
     */
    public function index() : InertiaResponse
    {
        $threads = Thread::all();
        return Inertia::render('Top', [
            'threads' => $threads
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreThreadRequest $request)
    {
        $thread = Thread::create([
            'title' => now()->format('Y-m-d H:i:s'),
        ]);

        return redirect()->route('thread.show', ['threadId' => $thread->id]);
    }

    /**
     * 英会話画面表示
     */
    public function show(int $threadId)
    {
        $messages = Message::where('thread_id', $threadId)->get();
        $thread = Thread::find($threadId);
        $threads = Thread::all();
        return Inertia::render('Thread/Show', [
            'thread' => $thread,
            'messages' => $messages,
            'threads' => $threads,
            'threadId' => $threadId
        ]);

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Thread $thread)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateThreadRequest $request, Thread $thread)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Thread $thread)
    {
        //
    }
}
