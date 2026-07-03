<?php

namespace App\Policies;

use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * No roles are defined for this application yet (and none are required by
 * the Inventory issue), so any authenticated/demo-resolved user is
 * authorized. This mirrors SupplierPolicy/PurchasePolicy so real role
 * checks can be dropped in later without restructuring the code.
 */
class InventoryItemPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, InventoryItem $inventoryItem): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, InventoryItem $inventoryItem): bool
    {
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, InventoryItem $inventoryItem): bool
    {
        return true;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, InventoryItem $inventoryItem): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, InventoryItem $inventoryItem): bool
    {
        return false;
    }
}
