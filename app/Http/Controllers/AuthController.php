<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'phone_number' => 'required',
            'password' => 'required',
        ]);

        // Prepend +998 prefix
        $credentials['phone_number'] = '+998' . $credentials['phone_number'];

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended(route('families.index'));
        }

        return back()->withErrors([
            'phone_number' => 'The provided credentials do not match our records.',
        ]);
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        // Prepend +998 prefix and check unique
        $phoneNumber = '+998' . $request->phone_number;
        if (User::where('phone_number', $phoneNumber)->exists()) {
            return back()->withErrors(['phone_number' => 'The phone number has already been taken.'])->withInput();
        }

        $user = User::create([
            'name' => $request->name,
            'phone_number' => $phoneNumber,
            'password' => Hash::make($request->password),
        ]);

        Auth::login($user);

        return redirect()->route('families.index');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
