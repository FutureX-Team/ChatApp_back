<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tweet;
use App\Models\User;

class TweetController extends Controller
{
    // عرض التايملاين (جميع التغريدات بالترتيب الزمني)
    public function index()
    {
        $tweets = Tweet::with('user:id,username,avatar_url')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($tweets);
    }

    // فلترة التغريدات (حسب المكان أو عدد اللايكات أو الوقت)
    public function filter(Request $request)
    {
        $query = Tweet::with('user:id,username,avatar_url');

        if ($request->has('place_id')) {
            $query->where('place_id', $request->place_id);
        }
        if ($request->has('sort')) {
            if ($request->sort === 'popular') {
                $query->orderByDesc('up_count');
            } elseif ($request->sort === 'recent') {
                $query->orderByDesc('created_at');
            }
        }

        $tweets = $query->get();
        return response()->json($tweets);
    }

    // عرض تغريدة محددة مع الردود
    public function show($id)
    {
        $tweet = Tweet::with([
            'user:id,username,avatar_url',
            'replies.user:id,username,avatar_url'
        ])->findOrFail($id);

        return response()->json($tweet);
    }

    // إنشاء تغريدة جديدة
    public function store(Request $request)
    {
        $request->validate([
            'text' => 'required|string|max:280',
            'place_id' => 'nullable|exists:places,id'
        ]);

        $tweet = Tweet::create([
            'user_id' => $request->user()->id,
            'text'    => $request->text,
            'place_id'=> $request->place_id,
        ]);

        return response()->json([
            'message' => 'Tweet created successfully',
            'tweet'   => $tweet
        ], 201);
    }

    // حذف تغريدة (لصاحبها فقط)
    public function destroy(Request $request, $id)
    {
        $tweet = Tweet::findOrFail($id);

        if ($tweet->user_id !== $request->user()->id) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $tweet->delete();
        return response()->json(['message' => 'Tweet deleted successfully']);
    }

    // الرد على تغريدة
    public function reply(Request $request, $id)
    {
        $request->validate([
            'text' => 'required|string|max:280'
        ]);

        $tweet = Tweet::findOrFail($id);

        $reply = Tweet::create([
            'user_id' => $request->user()->id,
            'text'    => $request->text,
            'reply_to_tweet_id' => $tweet->id,
        ]);

        return response()->json(['message' => 'Reply added', 'reply' => $reply], 201);
    }

    // لايك
    public function like(Request $request, $id)
    {
        $tweet = Tweet::findOrFail($id);
        $tweet->up_count += 1;
        $tweet->save();

        return response()->json(['message' => 'Liked', 'up_count' => $tweet->up_count]);
    }

    // ديسلايك
    public function dislike(Request $request, $id)
    {
        $tweet = Tweet::findOrFail($id);
        $tweet->down_count += 1;
        $tweet->save();

        return response()->json(['message' => 'Disliked', 'down_count' => $tweet->down_count]);
    }
}
