<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ToolsUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        [$a, $b] = $this->freshCaptcha();

        return view('auth.login', [
            'captchaA' => $a,
            'captchaB' => $b,
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'nama' => ['required', 'string'],
            'password' => ['required', 'string'],
            'captcha' => ['required', 'integer'],
        ]);

        $expected = (int) session('login_captcha_answer');
        $given = (int) $request->input('captcha');

        if ($expected === 0 || $given !== $expected) {
            [$a, $b] = $this->freshCaptcha();
            $request->flash();

            return view('auth.login', [
                'captchaA' => $a,
                'captchaB' => $b,
            ])->withErrors(['captcha' => 'Jawaban captcha salah. Silakan coba lagi.']);
        }

        $credentials = [
            'nama' => $request->input('nama'),
            'password' => $request->input('password'),
        ];

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            [$a, $b] = $this->freshCaptcha();
            $request->flash();

            return view('auth.login', [
                'captchaA' => $a,
                'captchaB' => $b,
            ])->withErrors(['nama' => 'Nama atau password salah.']);
        }

        $request->session()->regenerate();
        session()->forget('login_captcha_answer');

        return redirect()->intended('/');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    /**
     * @return array{0:int,1:int}
     */
    private function freshCaptcha(): array
    {
        $a = random_int(1, 9);
        $b = random_int(1, 9);
        session(['login_captcha_answer' => $a * $b]);

        return [$a, $b];
    }
}
