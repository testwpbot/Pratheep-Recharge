<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /** Show the registration form. */
    public function create(): View
    {
        return view('auth.register');
    }

    /** Handle a new registration. */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone'    => ['required', 'string', 'max:15', 'regex:/^\+?[0-9]{9,15}$/', 'unique:users,phone'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'phone.regex' => 'Please enter a valid phone number (e.g. +94771234567 or 0771234567).',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => strtolower($request->email),
            'phone'    => $this->normalizePhone($request->phone),
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        // Auto-create a wallet for every new customer
        Wallet::firstOrCreate(['user_id' => $user->id]);

        // Per request: redirect to the LOGIN page after signup (not auto-login)
        return redirect()
            ->route('login')
            ->with('status', 'Account created successfully! Please sign in.');
    }

    /**
     * Normalize LK phone numbers so input like 0771234567 becomes +94771234567.
     */
    protected function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^\d+]/', '', $phone);
        if (str_starts_with($phone, '0') && ! str_starts_with($phone, '+')) {
            $phone = '+94' . substr($phone, 1);
        }
        return $phone;
    }
}
