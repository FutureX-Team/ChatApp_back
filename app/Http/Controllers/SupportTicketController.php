<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use Illuminate\Http\Request;

class SupportTicketController extends Controller
{
    // POST /support
    public function store(Request $request)
    {
        $data = $request->validate([
            'username' => 'required|string|max:100',
            'email' => 'nullable|email',
            'message' => 'required|string',
        ]);

        $ticket = SupportTicket::create($data);

        return response()->json($ticket, 201);
    }

    // GET /admin/support-requests
    public function index()
    {
        return response()->json(
            SupportTicket::orderBy('created_at', 'desc')->get()
        );
    }
}
