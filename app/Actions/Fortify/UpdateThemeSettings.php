<?php

namespace App\Actions\Fortify;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UpdateThemeSettings
{
    /**
     * Update the given user's theme settings.
     */
    public function update($user, array $input)
    {
        // Pastikan input theme ada
        if (!isset($input['theme']) || !is_array($input['theme'])) {
            throw ValidationException::withMessages([
                'theme' => ['Data tema tidak valid.'],
            ]);
        }

        // Validasi dasar (cek tipe data string)
        Validator::make($input, [
            // Navbar
            'theme.navbar.from' => 'nullable|string|max:50',
            'theme.navbar.to' => 'nullable|string|max:50',
            'theme.navbar.shade_from' => 'nullable|string|max:50',
            'theme.navbar.shade_to' => 'nullable|string|max:50',
            'theme.navbar.name_color' => 'nullable|string|max:50',
            'theme.navbar.logo_color_from' => 'nullable|string|max:50',
            'theme.navbar.logo_color_to' => 'nullable|string|max:50',
            'theme.navbar.logo_color_shade_from' => 'nullable|string|max:50',
            'theme.navbar.logo_color_shade_to' => 'nullable|string|max:50',
            'theme.navbar.name_color_from' => 'nullable|string|max:50',
            'theme.navbar.name_color_to' => 'nullable|string|max:50',
            'theme.navbar.name_color_shade_from' => 'nullable|string|max:50',
            'theme.navbar.name_color_shade_to' => 'nullable|string|max:50',

            // Background
            'theme.background.from' => 'nullable|string|max:50',
            'theme.background.to' => 'nullable|string|max:50',
            'theme.background.shade_from' => 'nullable|string|max:50',
            'theme.background.shade_to' => 'nullable|string|max:50',

            // Menu
            'theme.menu.from' => 'nullable|string|max:50',
            'theme.menu.to' => 'nullable|string|max:50',
            'theme.menu.shade_from' => 'nullable|string|max:50',
            'theme.menu.shade_to' => 'nullable|string|max:50',
            'theme.menu.text_color' => 'nullable|string|max:50',

            // Button
            'theme.button.from' => 'nullable|string|max:50',
            'theme.button.to' => 'nullable|string|max:50',
            'theme.button.shade_from' => 'nullable|string|max:50',
            'theme.button.shade_to' => 'nullable|string|max:50',
            'theme.button.text_color' => 'nullable|string|max:50',
        ])->validateWithBag('updateTheme');

        // Ambil settings lama (kalau ada)
        $settings = $user->settings ?? [];

        // Gabungkan theme lama dengan yang baru (biar tidak hilang data lain)
        $settings['theme'] = array_replace_recursive($settings['theme'] ?? [], $input['theme']);

        // Simpan ke user
        $user->forceFill([
            'settings' => $settings,
        ])->save();
    }
}