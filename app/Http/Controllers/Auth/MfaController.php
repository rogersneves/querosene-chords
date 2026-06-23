<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\MfaCodeMail;
use App\Models\MfaCode;
use App\Models\MfaTrustedDevice;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class MfaController extends Controller
{
    /** Show the code entry form. */
    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('mfa_user_id')) {
            return redirect()->route('login');
        }

        return view('auth.mfa');
    }

    /** Verify the submitted code and complete login. */
    public function store(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('mfa_user_id');

        if (! $userId) {
            return redirect()->route('login');
        }

        // Rate-limit: 5 attempts per 5 minutes per user
        $key = "mfa:{$userId}";
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->withErrors(['code' => __('ui.auth.mfa_throttle', ['seconds' => $seconds])]);
        }

        $request->validate(['code' => ['required', 'digits:6']]);

        $record = MfaCode::where('user_id', $userId)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (! $record || ! Hash::check($request->input('code'), $record->code)) {
            RateLimiter::hit($key, 300);
            return back()->withErrors(['code' => __('ui.auth.mfa_invalid')])->withInput();
        }

        // Valid — clear attempts and code, complete login
        RateLimiter::clear($key);
        $record->delete();

        $user = User::findOrFail($userId);
        Auth::login($user, $request->session()->get('mfa_remember', false));
        $request->session()->forget(['mfa_user_id', 'mfa_remember']);
        $request->session()->regenerate();

        // Trust this browser for 30 days if requested
        if ($request->boolean('trust_device')) {
            $rawToken = MfaTrustedDevice::issue($user->id);
            cookie()->queue(
                cookie('mfa_device_token', $rawToken, 60 * 24 * 30, '/', null, false, true)
            );
        }

        return redirect()->intended(route('setlists.index'));
    }

    /** Resend a fresh code to the user's email. */
    public function resend(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('mfa_user_id');

        if (! $userId) {
            return redirect()->route('login');
        }

        $key = "mfa_resend:{$userId}";
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return back()->withErrors(['code' => __('ui.auth.mfa_resend_limit')]);
        }
        RateLimiter::hit($key, 300);

        $user = User::findOrFail($userId);
        self::sendCode($user);

        return back()->with('resent', true);
    }

    /** Generate and email a 6-digit code for the given user. */
    public static function sendCode(User $user): void
    {
        $code = (string) random_int(100000, 999999);

        // Replace any existing code for this user
        MfaCode::where('user_id', $user->id)->delete();
        MfaCode::create([
            'user_id'    => $user->id,
            'code'       => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
        ]);

        Mail::to($user->email)->send(new MfaCodeMail($code, $user->name));
    }
}
