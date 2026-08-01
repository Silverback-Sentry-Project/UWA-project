<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(
            Notification::where('user_id', $request->user()->user_id)
                ->latest('created_at')
                ->limit(50)
                ->get()
        );
    }

    public function markRead(Request $request, Notification $notification)
    {
        abort_if($notification->user_id !== $request->user()->user_id, 404);

        $notification->update(['is_read' => true]);

        return response()->json($notification);
    }
}
