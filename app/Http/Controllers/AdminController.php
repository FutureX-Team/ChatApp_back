<?php

namespace App\Http\Controllers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Tweet;
use App\Models\Report;

class AdminController extends Controller
{
    // GET /admin/stats
    public function stats()
    {
        $usersCount   = \App\Models\User::count();
        $tweetsCount  = \App\Models\Tweet::count();
        $reportsCount = \App\Models\Report::count();

        // اعتبر أن تغريدة الزائر user_id = null
        $registeredTweets = \App\Models\Tweet::whereNotNull('user_id')->count();
        $guestTweets      = \App\Models\Tweet::whereNull('user_id')->count();

        $lastMonthTweets = \App\Models\Tweet::where('created_at', '>=', Carbon::now()->subDays(30))->count();
        $lastWeekTweets  = \App\Models\Tweet::where('created_at', '>=', Carbon::now()->subDays(7))->count();

        // إن لم يكن لديك نظام presence، ارجع 0 أو احسب من آخر نشاط خلال 5 دقائق
        $onlineUsers = \App\Models\User::where('last_seen_at', '>=', Carbon::now()->subMinutes(5))->count();

        return response()->json([
            'users_count'        => $usersCount,
            'tweets_count'       => $tweetsCount,
            'reports_count'      => $reportsCount,
            'registered_tweets'  => $registeredTweets,
            'guest_tweets'       => $guestTweets,
            'last_month_tweets'  => $lastMonthTweets,
            'last_week_tweets'   => $lastWeekTweets,
            'online_users'       => $onlineUsers,
        ]);
    }

    // PUT /admin/users/{id}/toggle
    public function disable($id)
    {
        $user = User::findOrFail($id);
        $user->is_disabled = (bool)!$user->is_disabled;
        $user->save();

        return response()->json(['message' => 'User status updated', 'user' => $user]);
    }

    // PUT /admin/users/{id}
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'username'   => 'sometimes|string|max:50|unique:users,username,' . $user->id,
            'email'      => 'sometimes|email|max:255|unique:users,email,' . $user->id,
            'role'       => 'sometimes|in:user,admin',
            'avatar_url' => 'nullable|string',
        ]);

        $user->fill($validated)->save();

        return response()->json(['message' => 'User updated successfully', 'user' => $user]);
    }

    // DELETE /admin/tweets/{id}
    public function destroy($id)
    {
        $tweet = Tweet::findOrFail($id);
        // تأكد أن علاقات الردود تمسح كاسكيد في المايجريشن، أو احذفها يدويًا قبل الحذف
        $tweet->delete();

        return response()->json(['message' => 'Tweet deleted successfully']);
    }

    // GET /admin/reports?status=new|reviewing|resolved
    public function index(Request $request)
    {
        $q = Report::query()->with([
            'tweet:id,text,user_id,created_at',
            'user:id,username,email',
        ])->orderBy('created_at', 'desc');

        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }

        // ترجع { data, links, meta } مريحة للفرونت
        return response()->json($q->paginate(20));
    }

    // PUT /admin/reports/{id}
    public function updateReport(Request $request, $id)
    {
        $report = Report::findOrFail($id);

        $validated = $request->validate(['status' => 'required|in:pending,reviewed,resolved']);


        $report->status = $validated['status'];
        $report->save();

        return response()->json(['message' => 'Report updated successfully', 'report' => $report]);
    }
}
