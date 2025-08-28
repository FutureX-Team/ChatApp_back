<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Tweet;
use App\Models\Report;

class AdminController extends Controller
{
    // عرض إحصائيات عامة (عدد المستخدمين، التغريدات، البلاغات)
    public function stats()
    {
        $stats = [
            'users_count'   => User::count(),
            'tweets_count'  => Tweet::count(),
            'reports_count' => Report::count(),
        ];

        return response()->json($stats);
    }

    // تعطيل/تفعيل مستخدم
    public function disable($id)
    {
        $user = User::findOrFail($id);
        $user->is_disabled = !$user->is_disabled;
        $user->save();

        return response()->json(['message' => 'User status updated', 'user' => $user]);
    }

    // تحديث بيانات مستخدم (اسم، ايميل، صورة، دور)
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'username' => 'sometimes|string|max:50',
            'email'    => 'sometimes|email|max:255',
            'role'     => 'sometimes|in:user,admin',
            'avatar_url' => 'nullable|string'
        ]);

        $user->update($request->all());

        return response()->json(['message' => 'User updated successfully', 'user' => $user]);
    }

    // حذف تغريدة (مثلاً عليها بلاغ أو محتوى مخالف)
    public function destroy($id)
    {
        $tweet = Tweet::findOrFail($id);
        $tweet->delete();

        return response()->json(['message' => 'Tweet deleted successfully']);
    }

    // عرض جميع البلاغات
    public function index()
    {
        $reports = Report::with(['tweet', 'user'])->orderBy('created_at', 'desc')->get();

        return response()->json($reports);
    }

    // تحديث حالة البلاغ (pending → reviewed → resolved)
    public function updateReport(Request $request, $id)
    {
        $report = Report::findOrFail($id);

        $request->validate([
            'status' => 'required|in:pending,reviewed,resolved'
        ]);

        $report->status = $request->status;
        $report->save();

        return response()->json(['message' => 'Report updated successfully', 'report' => $report]);
    }
}
