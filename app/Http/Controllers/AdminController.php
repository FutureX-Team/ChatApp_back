<?php

namespace App\Http\Controllers;

use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Tweet;
use App\Models\Report;

class AdminController extends Controller
{
    /** Allowed enum values for reports.status */
    private const STATUSES = ['pending','reviewed','resolved'];

    // GET /admin/dashboard
    public function stats()
    {
        $usersCount   = User::count();
        $tweetsCount  = Tweet::count();
        $reportsCount = Report::count();

        $registeredTweets = Tweet::whereNotNull('user_id')->count();
        $guestTweets      = Tweet::whereNull('user_id')->count();

        $lastWeekTweets  = Tweet::where('created_at', '>=', Carbon::now()->subDays(7))->count();
        $lastWeekUsers   = User::where('created_at', '>=', Carbon::now()->subDays(7))->count();

        return response()->json([
            'users_count'        => $usersCount,
            'tweets_count'       => $tweetsCount,
            'reports_count'      => $reportsCount,
            'registered_tweets'  => $registeredTweets,
            'guest_tweets'       => $guestTweets,
            'last_week_users'    => $lastWeekUsers,
            'last_week_tweets'   => $lastWeekTweets,
        ]);
    }

    // PUT /admin/users/{id}/disable
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
        $tweet->delete();
        return response()->json(['message' => 'Tweet deleted successfully']);
    }

    // GET /admin/reports?status=pending|reviewed|resolved
    public function index(Request $request)
    {
        $q = Report::with([
                'tweet:id,text,user_id,created_at',
                'user:id,username,email',
            ])
            ->latest('id');

        if ($request->filled('status')) {
            $status = $request->string('status')->toString();
            if (! in_array($status, self::STATUSES, true)) {
                return response()->json(['message' => 'Invalid status'], 422);
            }
            $q->where('status', $status);
        }

        // returns { data, links, meta }
        return response()->json($q->paginate(20));
    }

    // PUT /admin/reports/{id}
    public function updateReport(Request $request, $id)
    {
        $data = $request->validate([
            'status' => 'required|in:' . implode(',', self::STATUSES),
        ]);

        $report = Report::findOrFail($id);
        $report->status = $data['status'];
        $report->save();

        return response()->json(['message' => 'Report updated successfully', 'report' => $report]);
    }
}
