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
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        try {
            // stateless() helps during dev when ports differ (SPA)
            $g = Socialite::driver('google')->stateless()->user();

            $user = User::where('google_id', $g->getId())->first();

            if (! $user) {
                // If you allow merging by email:
                $user = User::where('email', $g->getEmail())->first();

                if ($user) {
                    $user->update([
                        'google_id'  => $g->getId(),
                        'avatar_url' => $g->getAvatar(),
                    ]);
                } else {
                    // Your schema uses username + password_hash (nullable)
                    $username = Str::slug(explode('@', $g->getEmail())[0]);
                    // ensure unique username
                    $base = $username; $i = 1;
                    while (User::where('username', $username)->exists()) {
                        $username = $base.'-'.$i++;
                    }

                    $user = User::create([
                        'username'    => $username,
                        'email'       => $g->getEmail(),
                        'google_id'   => $g->getId(),
                        'avatar_url'  => $g->getAvatar(),
                        'password_hash' => null, 
                    ]);
                }
            }

            Auth::login($user, remember: true);

            // Send back to your SPA; session cookie is already set
            $frontend = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173'));
            return redirect($frontend . '/auth/callback?ok=1');

        } catch (Throwable $th) {
            report($th);
            return redirect((env('FRONTEND_URL') ?: '/') . '/login?error=google');
        }
    }
}
