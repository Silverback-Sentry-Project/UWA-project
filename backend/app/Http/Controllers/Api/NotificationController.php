<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // Plain array (not paginated) - the portal's notification bell fetches this on every
    // page load and expects NotificationRow[] directly, not a { data: [...] } wrapper.
    public function index(Request $request)
    {
        $notifications = Notification::where('user_id', $request->user()->user_id)
            ->latest('created_at')
            ->limit(50)
            ->get();

        return response()->json($notifications);
    }

    public function markRead(Request $request, Notification $notification)
    {
        if ($notification->user_id !== $request->user()->user_id) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $notification->update(['is_read' => true]);

        return response()->json($notification);
    }
}
