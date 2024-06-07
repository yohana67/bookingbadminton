<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeed extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = [
            [
                'name'           => 'admin',
                'telp'          =>  '087711166288',
                'email'          => 'admin@example.com',
                'password'       => bcrypt('123'),
                'remember_token' => null,
            ],
        ];

        User::insert($users);
    }
}
