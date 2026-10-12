<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AkunController extends Controller
{
    public function edit(): View
    {
        return view('akun.password');
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'max:200', 'confirmed', 'different:current_password', Password::defaults()],
        ]);

        $user = $request->user();
        $user->forceFill([
            'password' => $request->input('password'),
            'must_change_password' => false,
        ])->save();

        // Perangkat lain yang masih login dengan password lama ikut dikeluarkan.
        $user->hapusSesiLain($request->session()->getId());

        return redirect()->route('dashboard')->with('status', 'Password berhasil diganti.');
    }
}
