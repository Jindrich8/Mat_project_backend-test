<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::factory(10)->create();
    //    $users = User::factory(15)->make()->toArray();
    //     foreach($users as $user){
    //         User::create($user);
    //     }
    }
}
