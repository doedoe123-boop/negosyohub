<?php

namespace App\Policies;

use App\Models\PropertyInquiry;
use App\Models\User;

/**
 * Authorization gate for PropertyInquiry.
 *
 * Inquiries are submitted publicly (no auth required) but
 * only admins and the owning store's agent may view or edit them.
 */
class PropertyInquiryPolicy
{
    public function viewAny(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->isAdmin() || $user->isStoreOwner();
    }

    public function view(?User $user, PropertyInquiry $inquiry): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isStoreOwner() && $user->store?->id === $inquiry->store_id;
    }

    public function create(?User $user): bool
    {
        return true; // Public submission
    }

    public function update(User $user, PropertyInquiry $inquiry): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isStoreOwner() && $user->store?->id === $inquiry->store_id;
    }

    public function delete(User $user, PropertyInquiry $inquiry): bool
    {
        return $user->isAdmin();
    }
}
