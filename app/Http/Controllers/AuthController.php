<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller{
    public function login()
    {
        return view('auth.login');
    }

    public function authenticate(Request $request)
    {
        $request->validate([
            'phone' => 'required'
        ]);

        // faqat bitta telefon raqamga ruxsat beriladi
        if ($request->phone !== "998888158822007") {
            return redirect()->route('login')->withErrors([
                'phone' => 'Sizning telefon raqamingiz ruxsat etilmagan.'
            ]);
        }

        // shu telefon raqamli userni topamiz
        $user = User::where('phone_number', "998888158822007")->first();

        if (!$user) {
            return redirect()->route('login')->withErrors([
                'phone' => 'Foydalanuvchi topilmadi.'
            ]);
        }

        // Login qilamiz
        Auth::login($user);

        return redirect()->route('admin.index');
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('login');
    }
}
