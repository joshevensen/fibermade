<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Order;
use App\Models\Store;

class WholesaleOrderMailHelper
{
    public static function getStoreEmail(Order $order): ?string
    {
        $orderable = $order->orderable;

        if (! $orderable instanceof Store) {
            return null;
        }

        $email = $orderable->email;
        if (! empty(trim((string) $email))) {
            return $email;
        }

        $owner = $orderable->account?->users()
            ->where('role', UserRole::Owner)
            ->first();

        return $owner?->email;
    }

    public static function getCreatorEmail(Order $order): ?string
    {
        $creator = $order->account?->creator;
        $email = $creator?->email;
        if (! empty(trim((string) $email))) {
            return $email;
        }

        $owner = $order->account?->users()
            ->where('role', UserRole::Owner)
            ->first();

        return $owner?->email;
    }
}
