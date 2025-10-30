<?php

namespace App\Http\Middleware;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        // Pastikan role user dimuat (untuk Spatie Permission)
        if ($user && !$user->relationLoaded('roles')) {
            $user->load('roles');
        }

        // Cari pasangan (partner)
        $partner = null;
        if ($user && $user->family_id) {
            // Dapatkan role lawan berdasarkan role user
            $oppositeRoles = $this->getOppositeRoles($user);

            $partner = User::where('family_id', $user->family_id)
                ->where('id', '!=', $user->id)
                ->whereHas('roles', function ($query) use ($oppositeRoles) {
                    $query->whereIn('name', $oppositeRoles);
                })
                ->first(['id', 'name', 'email', 'profile_photo_path']);
        }

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'profile_photo_url' => $user->profile_photo_url,
                    'roles' => $user->getRoleNames(), // Tambahkan untuk debugging

                    // Theme (jika ada)
                    'theme' => $this->getUserTheme($user->settings ?? []),

                    // Data keluarga
                    'family' => $user->family
                        ? [
                            'id' => $user->family->id,
                            'name' => $user->family->name,
                        ]
                        : null,

                    // Pasangan (partner)
                    'partner' => $partner ? [
                        'id' => $partner->id,
                        'name' => $partner->name,
                        'email' => $partner->email,
                        'profile_photo_url' => $partner->profile_photo_url,
                    ] : null,
                ] : null,
            ],
                'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'type' => fn () => $request->session()->get('type'),
            ],
        ]);
    }

    /**
     * Tentukan role pasangan berdasarkan role user (Spatie Permission).
     */
    protected function getOppositeRoles($user)
    {
        $role = $user->getRoleNames()->first();

        return match ($role) {
            'Suami' => ['Istri', 'Calon Istri'],
            'Calon Suami' => ['Calon Istri', 'Istri'],
            'Istri' => ['Suami', 'Calon Suami'],
            'Calon Istri' => ['Calon Suami', 'Suami'],
            default => ['Suami', 'Istri', 'Calon Suami', 'Calon Istri'],
        };
    }

    /**
     * Ambil theme user dengan default values
     */
    private function getUserTheme(array $settings): ?string
    {
        $theme = $settings['theme'] ?? null;

        return $theme ? json_encode($theme) : null;
    }
}
