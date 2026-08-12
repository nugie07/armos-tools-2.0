<?php

namespace Database\Seeders;

use App\Models\ToolsUser;
use Illuminate\Database\Seeder;

class ToolsUserSeeder extends Seeder
{
    public function run(): void
    {
        ToolsUser::query()->updateOrCreate(
            ['nama' => 'admin'],
            ['password' => 'admin12345']
        );
    }
}
