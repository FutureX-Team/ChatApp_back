<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Report;
use App\Models\Tweet;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{

    public function store(Request $request)
    {
        $start = microtime(true);
        $uid   = Auth::id(); // ← لا تعتمد على $request->user()->id مباشرة

        if (!$uid) {
            Log::warning('REPORTS.STORE unauthenticated', ['ip' => $request->ip()]);
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $data = $request->validate([
            'tweet_id' => 'required|integer|exists:tweets,id',
            'reason'   => 'required|string|max:1000',
        ]);

        $tweet = Tweet::findOrFail($data['tweet_id']);

        // منع الإبلاغ عن تغريدتي
        if ($tweet->user_id === $uid) {
            Log::info('REPORTS.STORE own_tweet_blocked', [
                'user_id'  => $uid,
                'tweet_id' => $tweet->id,
            ]);
            return response()->json(['message' => 'لا يمكنك الإبلاغ عن تغريدتك.'], 422);
        }

        // منع تكرار البلاغ المفتوح
        $alreadyOpen = Report::where('tweet_id', $data['tweet_id'])
            ->where('user_id', $uid)
            ->where('status', '!=', 'resolved')
            ->exists();

        if ($alreadyOpen) {
            Log::info('REPORTS.STORE duplicate_blocked', [
                'user_id'  => $uid,
                'tweet_id' => $data['tweet_id'],
            ]);
            return response()->json(['message' => 'سبق وأن بلّغت عن هذه التغريدة ولم يُغلق البلاغ بعد.'], 409);
        }

        $report = Report::create([
            'tweet_id' => $data['tweet_id'],
            'user_id'  => $uid,
            'reason'   => $data['reason'],
            'status'   => 'pending',
        ])->load([
            'tweet:id,user_id,text,created_at',
            'tweet.user:id,username',
        ]);

        Log::info('REPORTS.STORE created', [
            'user_id'     => $uid,
            'tweet_id'    => $data['tweet_id'],
            'report_id'   => $report->id,
            'duration_ms' => round((microtime(true) - $start) * 1000, 2),
        ]);

        return response()->json([
            'message' => 'تم إرسال البلاغ بنجاح',
            'report'  => $report,
        ], 201);
    }


    /**
     * GET /reports/mine
     */
    public function myReports(Request $request)
    {
        $start = microtime(true);
        $uid   = optional($request->user())->id;

        Log::info('REPORTS.MINE start', [
            'user_id' => $uid,
            'ip'      => $request->ip(),
        ]);

        $reports = Report::with([
            'tweet:id,user_id,text,created_at',
            'tweet.user:id,username'
        ])
            ->where('user_id', $uid)
            ->orderByDesc('created_at')
            ->get();

        Log::info('REPORTS.MINE done', [
            'user_id'     => $uid,
            'count'       => $reports->count(),
            'duration_ms' => round((microtime(true) - $start) * 1000, 2),
        ]);

        return response()->json($reports);
    }
}
