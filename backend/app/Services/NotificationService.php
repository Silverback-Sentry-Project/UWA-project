<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Rangers are notified through the existing mobile/FCM path (see BRIDGE-CONTRACT.md) - this
 * service is entirely for the web portal's own notification bell, so every recipient here is
 * always a portal-eligible role (User::canAccessPortal()).
 */
class NotificationService
{
    public function notifyParkStaff(int $parkId, string $title, string $message, string $type): void
    {
        $this->notifyUsers(
            User::where('park_id', $parkId)
                ->whereHas('roles', fn ($q) => $q->whereIn('role_name', ['Park Warden', 'Gamepark Officer']))
                ->get(),
            $title,
            $message,
            $type,
        );
    }

    public function notifyUwaOfficials(string $title, string $message, string $type): void
    {
        $this->notifyUsers(
            User::whereHas('roles', fn ($q) => $q->where('role_name', 'UWA Official'))->get(),
            $title,
            $message,
            $type,
        );
    }

    public function notifyUser(?User $user, string $title, string $message, string $type): void
    {
        if ($user !== null) {
            $this->notifyUsers(collect([$user]), $title, $message, $type);
        }
    }

    /**
     * @param  Collection<int, User>  $users
     */
    private function notifyUsers($users, string $title, string $message, string $type): void
    {
        foreach ($users as $user) {
            Notification::create([
                'user_id' => $user->user_id,
                'title' => $title,
                'message' => $message,
                'notification_type' => $type,
            ]);
        }
    }
}
