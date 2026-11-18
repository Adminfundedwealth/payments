<?php

namespace App\Http\Controllers;

use App\Models\User;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        if (! $this->isConfigured()) {
            return redirect()->route('login')
                ->withErrors(['google' => 'Google sign-in is not configured. Please try email and password or contact support.']);
        }

        return Socialite::driver('google')
            ->scopes(['openid', 'email', 'profile'])
            ->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        if (! $this->isConfigured()) {
            return redirect()->route('login')
                ->withErrors(['google' => 'Google sign-in is not configured. Please try email and password or contact support.']);
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException) {
            return redirect()->route('login')
                ->withErrors(['google' => 'Google sign-in expired or could not be verified. Please try again.']);
        } catch (IdentityProviderException|GuzzleException) {
            return redirect()->route('login')
                ->withErrors(['google' => 'Google sign-in could not be completed. Please try again.']);
        }

        $googleId = (string) $googleUser->getId();
        $email = mb_strtolower(trim((string) $googleUser->getEmail()));
        $name = trim((string) $googleUser->getName());

        if ($googleId === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)
            || ($googleUser->getRaw()['email_verified'] ?? false) !== true || $name === '') {
            return redirect()->route('login')
                ->withErrors(['google' => 'Google must provide a verified email address and profile name to sign in.']);
        }

        try {
            $user = DB::transaction(function () use ($googleId, $email, $name) {
                $user = User::query()->where('google_id', $googleId)->lockForUpdate()->first();

                if ($user && mb_strtolower($user->email) !== $email) {
                    return null;
                }

                $user ??= User::query()->whereRaw('LOWER(email) = ?', [$email])->lockForUpdate()->first();

                if ($user) {
                    if ($user->role !== 'merchant' || ! $user->is_active
                        || ($user->google_id !== null && $user->google_id !== $googleId)) {
                        return null;
                    }

                    if ($user->google_id === null) {
                        $user->forceFill([
                            'google_id' => $googleId,
                            'email_verified_at' => $user->email_verified_at ?? now(),
                        ])->save();
                    }

                    return $user;
                }

                if (! config('auth.allow_registration', false)) {
                    return null;
                }

                $user = User::create([
                    'name' => $name,
                    'business_name' => $name,
                    'email' => $email,
                    'password' => Str::random(64),
                    'role' => 'merchant',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]);
                $user->forceFill([
                    'google_id' => $googleId,
                    'email_verified_at' => now(),
                ])->save();
                $user->regenerateApiCredentials();

                return $user;
            });
        } catch (QueryException $exception) {
            if (! $this->isUniqueIdentityConflict($exception)) {
                throw $exception;
            }

            $user = User::query()
                ->where('google_id', $googleId)
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first();

            if (! $user || $user->role !== 'merchant' || ! $user->is_active) {
                return redirect()->route('login')
                    ->withErrors(['google' => 'This Google account could not be linked. Sign in with email and password or contact support.']);
            }
        }

        if (! $user) {
            return redirect()->route('login')
                ->withErrors(['google' => config('auth.allow_registration', false)
                    ? 'This Google account cannot be linked to an active merchant account.'
                    : 'Registration is currently unavailable. You can sign in with Google once your merchant account has been linked.']);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    private function isConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }

    private function isUniqueIdentityConflict(QueryException $exception): bool
    {
        $message = $exception->getMessage();

        return $exception->getCode() === '23000'
            && (str_contains($message, 'users_google_id_unique')
                || str_contains($message, 'users.google_id')
                || str_contains($message, 'users_email_unique')
                || str_contains($message, 'users.email'));
    }
}
