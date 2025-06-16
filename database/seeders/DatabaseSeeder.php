<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        // \App\Models\User::factory(10)->create();

        $user = User::create([
            'id'=>100,
            'username' => 'omar',
            'password' => Hash::make('123'),
            'type'=>1,
            'status'=>1,
            'approved_at'=>now()
        ]);


        $permissions = [
            'create_user',
            'edit_user',
            'delete_user',
            'update_user',
            'view_user',
            'restore_user',
            'restore_any_user',
            'replicate_user',
            'reorder_user',
            'view_any_ticket',
            'create_ticket',
            'update_ticket',
            'delete_ticket',
            'restore_ticket',
            'view_any_user',
            'create_role',
            'edit_roles',
            'delete_roles',
            'view_role',
            'view_any_role',
            'create_role',
            'update_role',
        ];
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
        $superAdminRole = Role::firstOrCreate(['name' => 'super admin']);
        $superAdminRole->syncPermissions($permissions);
        $user->assignRole('super admin');
        Admin::create([
            'user_id' => $user->id,
            'name' => 'omar manea',
            'system_id'=>1
        ]);

    }
}
