<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Notification::latest()->paginate(20);
        return view('notifications.index', compact('notifications'));
    }

    public function markAsRead(Notification $notification)
    {
        $notification->update(['is_read' => true]);
        return back()->with('success', 'Notification marked as read.');
    }

    public function markAllAsRead()
    {
        Notification::unread()->update(['is_read' => true]);
        return back()->with('success', 'All notifications marked as read.');
    }
}
