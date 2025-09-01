<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Report;
use App\Models\Tweet;

class ReportController extends Controller
{
    /**
     * POST /reports
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'tweet_id' => 'required|exists:tweets,id',
            'reason'   => 'required|string|max:1000',
        ]);

        // لا تسمح بالإبلاغ عن تغريدته الخاصة (اختياري)
        $tweet = Tweet::findOrFail($data['tweet_id']);
        if ($tweet->user_id === $request->user()->id) {
            return response()->json(['message' => 'لا يمكنك الإبلاغ عن تغريدتك.'], 422);
        }

        // منع التكرار: لو فيه بلاغ سابق لنفس التغريدة من نفس المستخدم ولم يُغلق بعد
        $alreadyOpen = Report::where('tweet_id', $data['tweet_id'])
            ->where('user_id', $request->user()->id)
            ->where('status', '!=', 'resolved')
            ->exists();

        if ($alreadyOpen) {
            return response()->json(['message' => 'سبق وأن بلّغت عن هذه التغريدة ولم يُغلق البلاغ بعد.'], 409);
        }

        // الحالة الافتراضية pending (تأكد منها في الـ migration)
        $report = Report::create([
            'tweet_id' => $data['tweet_id'],
            'user_id'  => $request->user()->id,
            'reason'   => $data['reason'],
            'status'   => 'pending',
        ]);

        // رجّع البلاغ مع التغريدة والناشر
        $report->load([
            'tweet:id,user_id,text,created_at',
            'tweet.user:id,username'
        ]);

        return response()->json([
            'message' => 'تم إرسال البلاغ بنجاح',
            'report'  => $report
        ], 201);
    }

    /**
     * GET /reports/mine
     */
    public function myReports(Request $request)
    {
        $reports = Report::with([
            'tweet:id,user_id,text,created_at',
            'tweet.user:id,username'
        ])
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json($reports);
    }
}
