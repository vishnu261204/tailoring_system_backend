<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        User::create([
            'username' => 'admin',
            'email' => 'admin@tailoring.com',
            'password' => Hash::make('Admin@123'),
            'full_name' => 'Administrator',
            'role' => 'admin',
            'is_active' => true
        ]);

        User::create([
            'username' => 'staff1',
            'email' => 'staff@tailoring.com',
            'password' => Hash::make('Staff@123'),
            'full_name' => 'Staff Member',
            'role' => 'staff',
            'is_active' => true
        ]);
    }
}