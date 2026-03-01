<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Service\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\Response;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse|Response
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(UserService $userService): RedirectResponse|Response
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')->with('message', __('Authentication with Google failed. Please try again.'));
        }

        try {
            $user = $userService->findOrCreateUserFromGoogle($googleUser);
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $firstMessage = $errors['email'][0] ?? $e->getMessage();

            return redirect()->route('login')->with('message', $firstMessage);
        }

        Auth::login($user, true);

        $redirectPath = session()->pull('url.intended', route('dashboard', [], false));

        return Inertia::location($redirectPath);
    }
}
