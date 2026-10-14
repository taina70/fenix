<?php
namespace Database\Seeders;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email'=>'professor@teste.com'],
            [
                'name' => 'Professor Silva',
                'role' => 'teacher',
            ]
        );

        User::firstOrCreate(
            ['email' => 'aluno1@teste.com'],
            [
                'name' => 'Maria Oliveira',
                'role' => 'student',
            ]
        );
    }
}
