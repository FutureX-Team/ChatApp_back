<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tweet;
use Illuminate\Support\Facades\Log;

class TweetController extends Controller
{
    // قائمة التغريدات
    public function index(Request $request)
    {
        $start = microtime(true);
        Log::info('TWEETS.INDEX start', [
            'page' => (int) $request->query('page', 1),
        ]);

        $tweets = Tweet::query()
            ->with(['user:id,username,avatar_url'])
            ->withCount('replies')
            ->latest('created_at')      // ← ترتيب بحسب التاريخ تنازلي
            ->orderByDesc('id')         // ← كسر تعادل لو نفس الدقيقة/الثانية
            ->paginate(20);

        Log::info('TWEETS.INDEX done', [
            'items'       => $tweets->count(),
            'currentPage' => $tweets->currentPage(),
            'lastPage'    => $tweets->lastPage(),
            'duration_ms' => round((microtime(true) - $start) * 1000, 2),
        ]);

        return response()->json($tweets);
    }

    // فلترة التغريدات
    // filter(): تأكدنا من القراءة الصحيحة لـ sort ورتّبنا الأحدث أولاً افتراضياً
    public function filter(Request $request)
    {
        $start = microtime(true);
        Log::info('TWEETS.FILTER start', [
            'place_id' => $request->input('place_id'),
            'sort'     => $request->input('sort'),
        ]);

        $q = Tweet::with('user:id,username,avatar_url')
            ->withCount(['replies as replies_count']);

        if ($request->filled('place_id')) {
            $q->where('place_id', (int) $request->input('place_id'));
        }

        $sort = $request->input('sort'); // ← بدل string() إلى input()
        if ($sort === 'popular') {
            $q->orderByDesc('up_count')
                ->orderByDesc('created_at')
                ->orderByDesc('id');
        } else {
            $q->latest('created_at')
                ->orderByDesc('id');
        }

        $tweets = $q->get([
            'id',
            'user_id',
            'text',
            'place_id',
            'reply_to_tweet_id',
            'up_count',
            'down_count',
            'created_at'
        ]);

        Log::info('TWEETS.FILTER done', [
            'count'       => $tweets->count(),
            'duration_ms' => round((microtime(true) - $start) * 1000, 2),
        ]);

        return response()->json($tweets);
    }


    // تغريدة واحدة + الردود
    public function show($id)
    {
        $start = microtime(true);
        Log::info('TWEETS.SHOW start', ['tweet_id' => (int) $id]);

        $tweet = Tweet::with([
            'user:id,username,avatar_url',
            'replies' => fn($q) => $q->with('user:id,username,avatar_url')
                ->latest('created_at')
                ->select(['id', 'user_id', 'text', 'place_id', 'reply_to_tweet_id', 'up_count', 'down_count', 'created_at']),
        ])->withCount(['replies as replies_count'])
            ->findOrFail($id, ['id', 'user_id', 'text', 'place_id', 'reply_to_tweet_id', 'up_count', 'down_count', 'created_at']);

        $tweet->setAttribute('replies_count', $tweet->replies->count());

        Log::info('TWEETS.SHOW done', [
            'tweet_id'    => (int) $id,
            'replies_cnt' => $tweet->replies->count(),
            'duration_ms' => round((microtime(true) - $start) * 1000, 2),
        ]);

        return response()->json($tweet);
    }

    // إنشاء تغريدة
    public function store(Request $request)
    {
        $start = microtime(true);
        $uid = optional($request->user())->id;

        if (!$uid) {
            Log::warning('TWEETS.STORE unauthenticated', ['ip' => $request->ip()]);
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $data = $request->validate([
            'text' => ['required', 'string', 'max:280'],
            'place_id' => ['nullable', 'exists:places,id'],
            'reply_to_tweet_id' => ['nullable', 'exists:tweets,id'],
        ]);

        // 👇 التحقق من عدد التغريدات خلال آخر دقيقة
        $recentCount = Tweet::where('user_id', $uid)
            ->where('created_at', '>=', now()->subMinute())
            ->count();

        if ($recentCount > 2) {
            return response()->json([
                'message' => 'لا يمكنك نشر أكثر من تغريدتين في الدقيقة الواحدة'
            ], 429); // 429 Too Many Requests
        }

        $tweet = Tweet::create([
            'user_id' => $uid,
            'text'    => $data['text'],
            'place_id' => $data['place_id'] ?? null,
            'reply_to_tweet_id' => $data['reply_to_tweet_id'] ?? null,
            'up_count' => 0,
            'down_count' => 0,
        ]);

        $tweet->load('user:id,username,avatar_url');

        Log::info('TWEETS.STORE created', [
            'user_id'     => $uid,
            'tweet_id'    => $tweet->id,
            'text_len'    => mb_strlen($data['text']),
            'duration_ms' => round((microtime(true) - $start) * 1000, 2),
        ]);

        return response()->json($tweet, 201);
    }


    // حذف تغريدة
    public function destroy(Request $request, $id)
    {
        $start = microtime(true);
        $uid = optional($request->user())->id;
        Log::info('TWEETS.DESTROY start', ['user_id' => $uid, 'tweet_id' => (int) $id]);

        $tweet = Tweet::findOrFail($id);

        if (!$uid || $tweet->user_id !== $uid) {
            Log::warning('TWEETS.DESTROY forbidden', [
                'user_id'  => $uid,
                'owner_id' => $tweet->user_id,
                'tweet_id' => $tweet->id,
            ]);
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $tweet->delete();

        Log::info('TWEETS.DESTROY done', [
            'user_id'     => $uid,
            'tweet_id'    => (int) $id,
            'duration_ms' => round((microtime(true) - $start) * 1000, 2),
        ]);

        return response()->json(['message' => 'Tweet deleted successfully']);
    }

    // إضافة رد
    public function reply(Request $request, $id)
    {
        $start = microtime(true);
        $uid = optional($request->user())->id;

        if (!$uid) {
            Log::warning('TWEETS.REPLY unauthenticated', ['ip' => $request->ip(), 'parent_id' => (int) $id]);
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $request->validate(['text' => ['required', 'string', 'max:280']]);

        $parent = Tweet::findOrFail($id);

        $reply = Tweet::create([
            'user_id' => $uid,
            'text'    => $request->input('text'),
            'reply_to_tweet_id' => $parent->id,
            'up_count' => 0,
            'down_count' => 0,
        ]);

        $reply->load('user:id,username,avatar_url');

        Log::info('TWEETS.REPLY created', [
            'user_id'     => $uid,
            'parent_id'   => $parent->id,
            'reply_id'    => $reply->id,
            'text_len'    => mb_strlen($request->input('text')),
            'duration_ms' => round((microtime(true) - $start) * 1000, 2),
        ]);

        return response()->json($reply, 201);
    }

    // لايك
    public function like(Request $request, $id)
    {
        $start = microtime(true);
        $uid = optional($request->user())->id;

        $tweet = Tweet::findOrFail($id);
        $tweet->increment('up_count');

        Log::info('TWEETS.LIKE', [
            'user_id'     => $uid,
            'tweet_id'    => (int) $id,
            'up_count'    => $tweet->up_count,
            'duration_ms' => round((microtime(true) - $start) * 1000, 2),
        ]);

        return response()->json(['message' => 'Liked', 'up_count' => $tweet->up_count]);
    }

    // ديسلايك
    public function dislike(Request $request, $id)
    {
        $start = microtime(true);
        $uid = optional($request->user())->id;

        $tweet = Tweet::findOrFail($id);
        $tweet->increment('down_count');

        Log::info('TWEETS.DISLIKE', [
            'user_id'      => $uid,
            'tweet_id'     => (int) $id,
            'down_count'   => $tweet->down_count,
            'duration_ms'  => round((microtime(true) - $start) * 1000, 2),
        ]);

        return response()->json(['message' => 'Disliked', 'down_count' => $tweet->down_count]);
    }
}
