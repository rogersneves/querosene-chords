<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\MfaTrustedDevice;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Verify credentials without creating a session yet
        if (! Auth::validate($credentials)) {
            return back()
                ->withErrors(['email' => __('ui.auth.invalid_credentials')])
                ->onlyInput('email');
        }

        $user = User::where('email', $credentials['email'])->first();

        // Check if this browser is already trusted (skip MFA)
        $cookieToken = $request->cookie('mfa_device_token');
        if ($cookieToken && MfaTrustedDevice::isValid($user->id, $cookieToken)) {
            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();
            return redirect()->intended(route('setlists.index'));
        }

        // Store pending state and send MFA code
        $request->session()->put('mfa_user_id', $user->id);
        $request->session()->put('mfa_remember', $request->boolean('remember'));

        MfaController::sendCode($user);

        return redirect()->route('mfa.verify');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home');
    }
}
