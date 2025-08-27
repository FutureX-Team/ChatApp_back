<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Report;
use App\Models\Tweet;

class ReportController extends Controller
{
      public function store(Request $request)
    {
        $request->validate([
            'tweet_id' => 'required|exists:tweets,id',
            'reason'   => 'required|string|max:1000',
        ]);

        // لا تسمح بالإبلاغ على تغريدته الخاصة (اختياري)
        $tweet = Tweet::findOrFail($request->tweet_id);
        if ($tweet->user_id === $request->user()->id) {
            return response()->json([
                'message' => 'لا يمكنك الإبلاغ عن تغريدتك.'
            ], 422);
        }

        $report = Report::create([
            'tweet_id' => $request->tweet_id,
            'user_id'  => $request->user()->id,
            // الحالة افتراضياً pending من قاعدة البيانات
            'reason'   => $request->reason,
        ]);

        // نرجع البلاغ مع التغريدة المرتبطة
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
     * GET /reports
     * إظهار البلاغات التي أرسلتها (My Reports)
     */
    public function mine(Request $request)
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
