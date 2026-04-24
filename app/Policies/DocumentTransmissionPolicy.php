<?php

namespace App\Policies;

use App\Models\DocumentTransmission;
use App\Models\User;

class DocumentTransmissionPolicy
{
    public function view(User $user, DocumentTransmission $transmission): bool
    {
        return $user->id === $transmission->sender_id
            || $user->id === $transmission->receiver_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function receive(User $user, DocumentTransmission $transmission): bool
    {
        return $user->id === $transmission->receiver_id;
    }

    public function forward(User $user, DocumentTransmission $transmission): bool
    {
        if ($transmission->status !== DocumentTransmission::STATUS_COMPLETED) {
            return false;
        }

        return $user->id === $transmission->sender_id
            || $user->id === $transmission->receiver_id;
    }
}
