<?php

namespace Database\Seeders;

use App\Models\User;
use Hash;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin_role = Role::create(['name' => 'admin']);
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@vinawebapp.com',
            'password' => Hash::make('admin@123'),
        ]);
        $admin->assignRole($admin_role);
        $userRole = Role::create(['name' => 'user']);
        $user = User::factory()->create([
            'name' => 'Regular User',
            'email' => 'user@vinawebapp.com',
            'password' => Hash::make('user@123'),
        ]);
        $user->assignRole($userRole);
    }
}
