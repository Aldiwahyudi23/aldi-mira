<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Family;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Buat role
        $suami = Role::firstOrCreate(['name' => 'Suami']);
        $istri = Role::firstOrCreate(['name' => 'Istri']);
        $Calonsuami = Role::firstOrCreate(['name' => 'Calon Suami']);
        $Calonistri = Role::firstOrCreate(['name' => 'Calon Istri']);

        // 🔹 Buat Family
        $family = Family::create([
            'name' => 'Almir',
            'invitation_code' => '1-januari-2025',
            'settings' => json_encode(['theme' => 'default']),
            'is_active' => true,
        ]);

        // 🔹 Buat akun suami
        $suamiUser = User::create([
            'name' => 'Aldi Wahyudi',
            'email' => 'aldiwahyudi1223@gmail.com',
            'password' => bcrypt('miraulpah'),
            'family_id' => $family->id, // hubungan ke Family
        ]);
        $suamiUser->assignRole($suami);

        // 🔹 Buat akun istri
        $istriUser = User::create([
            'name' => 'Mira Ulpah',
            'email' => 'miraulpah1001@gmail.com',
            'password' => bcrypt('aldiwahyudi'),
            'family_id' => $family->id, // hubungan ke Family
        ]);
        $istriUser->assignRole($istri);
    }
}
