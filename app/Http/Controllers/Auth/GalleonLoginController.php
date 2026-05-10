<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class GalleonLoginController extends Controller
{
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt([
            'email'    => $credentials['email'],
            'password' => $credentials['password'],
        ], $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => trans('auth.failed')]);
        }

        $user = Auth::user();

        $hasTotp  = !empty($user->getAppAuthenticationSecret());
        $hasEmail = $user->hasEmailAuthentication();
        if ($hasTotp || $hasEmail) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => 'Galleon v2.0-EX experimental does not yet support 2FA. Use legacy panel or disable 2FA.',
            ]);
        }

        $request->session()->regenerate();
        return redirect()->intended(route('galleon.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('galleon.login');
    }
}