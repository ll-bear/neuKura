<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $defaultEmail = config('webPlot.default_user_email');
        if ($defaultEmail && !\App\Models\User::where('email', $defaultEmail)->exists()) {
            \App\Models\User::create([
                'name' => config('webPlot.default_user_name'),
                'email' => $defaultEmail,
                'password' => \Illuminate\Support\Facades\Hash::make(config('webPlot.default_user_password')),
            ]);
        }

        $this->call([
            CategorySeeder::class,
        ]);
    }
}
