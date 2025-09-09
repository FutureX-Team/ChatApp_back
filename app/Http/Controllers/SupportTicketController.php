<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SupportTicketController extends Controller
{
    // POST /support
    public function store(Request $request)
    {
        $start = microtime(true);
        $uid   = optional($request->user())->id;

        Log::info('SUPPORT.STORE start', [
            'user_id'    => $uid,
            'ip'         => $request->ip(),
            'user_agent' => $request->userAgent(),
            // لا نسجّل النص كاملًا؛ نكتفي بطول الرسالة للحساسية
            'username'   => $request->input('username'),
            'email'      => $request->input('email'),
            'msg_len'    => mb_strlen((string) $request->input('message')),
        ]);

        $data = $request->validate([
            'username' => 'required|string|max:100',
            'email'    => 'nullable|email',
            'message'  => 'required|string',
        ]);

        $ticket = SupportTicket::create($data);

        Log::info('SUPPORT.STORE created', [
            'user_id'     => $uid,
            'ticket_id'   => $ticket->id,
            'duration_ms' => round((microtime(true) - $start) * 1000, 2),
        ]);

        return response()->json($ticket, 201);
    }

    // GET /admin/support-requests  (أو /admin/support حسب مسارك)
    public function index(Request $request)
    {
        $start = microtime(true);
        $adminId = optional($request->user())->id;

        Log::info('SUPPORT.INDEX start', [
            'admin_id'  => $adminId,
            'ip'        => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $list = SupportTicket::orderBy('created_at', 'desc')->get();

        Log::info('SUPPORT.INDEX done', [
            'admin_id'    => $adminId,
            'count'       => $list->count(),
            'duration_ms' => round((microtime(true) - $start) * 1000, 2),
        ]);

        return response()->json($list);
    }
}
