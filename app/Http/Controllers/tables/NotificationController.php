<?php

namespace App\Http\Controllers\tables;

use App\Http\Controllers\Controller;
use App\Models\StoreNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(StoreNotification::where('user_id', $request->user()->id)->latest()->paginate(20));
    }

    public function markRead(Request $request, StoreNotification $notification)
    {
        abort_unless($notification->user_id === $request->user()->id, 403);
        $notification->update(['read_at' => now()]);

        return response()->json(['message' => 'Notification lue']);
    }

    public function markAllRead(Request $request)
    {
        StoreNotification::where('user_id', $request->user()->id)->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['message' => 'Notifications lues']);
    }
}
