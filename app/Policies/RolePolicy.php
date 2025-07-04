<?php

namespace App\Policies;


use App\Models\User;
use Illuminate\Auth\Access\Response;
use Spatie\Permission\Models\Role;

class RolePolicy{


   public function viewAny(User $user): bool
   {
       return $user->hasPermissionTo('view_any_role');
   }
   /**
    * Determine whether the user can view the model.
    */
   public function view(User $user, Role $role): bool
   {
       return $user->hasPermissionTo('view_role');
   }
   /**
    * Determine whether the user can create models.
    */
   public function create(User $user): bool
   {
       return $user->hasPermissionTo('create_role');
   }
   /**
    * Determine whether the user can update the model.
    */
   public function update(User $user, Role $role): bool
   {
       return $user->hasPermissionTo('update_role');
   }
   /**
    * Determine whether the user can delete the model.
    */
   public function delete(User $user, Role $role): bool
   {
       return $user->hasPermissionTo('delete_role');
   }
   /**
    * Determine whether the user can restore the model.
    */
   public function restore(User $user, Role $role): bool
   {
       //
   }
   /**
    * Determine whether the user can permanently delete the model.
    */
   public function forceDelete(User $user, Role $role): bool
   {
       //
   }
}
