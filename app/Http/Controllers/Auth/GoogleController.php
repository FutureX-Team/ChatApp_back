<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;

class GoogleController extends Controller
{
 public function redirect()
{
    return Socialite::driver('google')
        ->scopes(['openid','email','profile'])
        ->redirect();
}

public function callback()
{
    try {
        $g = Socialite::driver('google')->stateless()->user();

        $email = $g->getEmail();
        if (! $email) {
            return redirect(config('app.frontend_url').'/login?error=no-email');
        }

        $user = User::where('google_id', $g->getId())->first()
            ?? User::where('email', $email)->first();

        if (! $user) {
            $base = Str::slug(Str::before($email, '@')) ?: 'user';
            $username = $base;
            for ($i = 1; User::where('username', $username)->exists(); $i++) {
                $username = "{$base}-{$i}";
            }

            $user = User::create([
                'username' => $username,
                'email' => $email,
                'google_id' => $g->getId(),
                'avatar_url' => $g->getAvatar(),
                'password' => null,
            ]);
        } else {
            $user->update([
                'google_id' => $g->getId(),
                'avatar_url' => $g->getAvatar(),
            ]);
        }

        Auth::login($user, remember: true);

        return redirect(config('app.frontend_url', env('FRONTEND_URL','/')).'/auth/callback?ok=1');

    } catch (\Throwable $th) {
        report($th);
        return redirect(config('app.frontend_url', env('FRONTEND_URL','/')).'/login?error=google');
    }
}
}
