<?php

namespace App\Actions\Fortify;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DestroyTheme
{
    /**
     * Delete specific theme section from database
     */
    public function destroySection(Request $request)
    {
        // Validasi section yang ingin direset
        Validator::make($request->all(), [
            'section' => 'required|string|in:navbar,background,menu,button'
        ])->validateWithBag('destroyTheme');

        $section = $request->input('section');
        $settings = $request->user()->settings ?? [];
        
        // Hapus section tertentu dari theme
        if (isset($settings['theme'][$section])) {
            unset($settings['theme'][$section]);
            
            // Jika theme kosong setelah dihapus, hapus seluruh theme
            if (empty($settings['theme'])) {
                unset($settings['theme']);
            }
        }
        
        $request->user()->forceFill([
            'settings' => $settings,
        ])->save();

        return back()->with('status', 'theme-section-deleted');
    }

    /**
     * Delete all theme settings from database
     */
    public function destroyAll(Request $request)
    {
        $settings = $request->user()->settings ?? [];
        
        // Hapus seluruh theme
        if (isset($settings['theme'])) {
            unset($settings['theme']);
        }
        
        $request->user()->forceFill([
            'settings' => $settings,
        ])->save();

        return back()->with('status', 'theme-all-deleted');
    }
}