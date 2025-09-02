<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tweet;

class TweetController extends Controller
{
    // قائمة التغريدات
    public function index()
    {
        $tweets = Tweet::with('user:id,username,avatar_url')
            ->latest('id')
            ->get(['id','user_id','text','place_id','reply_to_tweet_id','up_count','down_count','created_at']);

        // ارجع المصفوفة مباشرة (frontend يتوقع object/list بدون تغليف)
        return response()->json($tweets);
    }

    // فلترة التغريدات
    public function filter(Request $request)
    {
        $q = Tweet::with('user:id,username,avatar_url');

        if ($request->filled('place_id')) {
            $q->where('place_id', $request->integer('place_id'));
        }

        if ($request->filled('sort')) {
            $request->string('sort') === 'popular'
                ? $q->orderByDesc('up_count')
                : $q->latest('id');
        } else {
            $q->latest('id');
        }

        $tweets = $q->get(['id','user_id','text','place_id','reply_to_tweet_id','up_count','down_count','created_at']);
        return response()->json($tweets);
    }

    // تغريدة واحدة + الردود
    public function show($id)
    {
        $tweet = Tweet::with([
            'user:id,username,avatar_url',
            'replies' => function ($q) {
                $q->with('user:id,username,avatar_url')
                  ->latest('id')
                  ->select(['id','user_id','text','place_id','reply_to_tweet_id','up_count','down_count','created_at']);
            },
        ])->findOrFail($id, ['id','user_id','text','place_id','reply_to_tweet_id','up_count','down_count','created_at']);

        // اختياري: عدّاد الردود ليساعد الواجهة
        $tweet->setAttribute('replies_count', $tweet->replies->count());

        return response()->json($tweet);
    }

    // إنشاء تغريدة
    public function store(Request $request)
    {
        if (! $request->user()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $data = $request->validate([
            'text' => ['required','string','max:280'],
            'place_id' => ['nullable','exists:places,id'],
            'reply_to_tweet_id' => ['nullable','exists:tweets,id'],
        ]);

        $tweet = Tweet::create([
            'user_id' => $request->user()->id,
            'text'    => $data['text'],
            'place_id' => $data['place_id'] ?? null,
            'reply_to_tweet_id' => $data['reply_to_tweet_id'] ?? null,
            'up_count' => 0,
            'down_count' => 0,
        ]);

        // أهم سطر: رجّع التغريدة مع صاحبها مباشرة
        $tweet->load('user:id,username,avatar_url');

        // لا تغلف النتيجة داخل {tweet: ...}
        return response()->json($tweet, 201);
    }

    // حذف تغريدة
    public function destroy(Request $request, $id)
    {
        $tweet = Tweet::findOrFail($id);

        if (! $request->user() || $tweet->user_id !== $request->user()->id) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $tweet->delete();
        return response()->json(['message' => 'Tweet deleted successfully']);
    }

    // إضافة رد
    public function reply(Request $request, $id)
    {
        if (! $request->user()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $request->validate(['text' => ['required','string','max:280']]);

        $parent = Tweet::findOrFail($id);

        $reply = Tweet::create([
            'user_id' => $request->user()->id,
            'text'    => $request->input('text'),
            'reply_to_tweet_id' => $parent->id,
            'up_count' => 0,
            'down_count' => 0,
        ]);

        // رجّع الرد مباشرة ومعه user
        $reply->load('user:id,username,avatar_url');

        return response()->json($reply, 201);
    }

    // لايك
    public function like(Request $request, $id)
    {
        $tweet = Tweet::findOrFail($id);
        $tweet->increment('up_count');

        return response()->json(['message' => 'Liked', 'up_count' => $tweet->up_count]);
    }

    // ديسلايك
    public function dislike(Request $request, $id)
    {
        $tweet = Tweet::findOrFail($id);
        $tweet->increment('down_count');

        return response()->json(['message' => 'Disliked', 'down_count' => $tweet->down_count]);
    }
}
