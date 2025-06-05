<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Log;


class AuthController extends Controller
{

    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'nama' => 'required|string|max:255',
                'email' => 'required|email|unique:pengguna,email',
                'kata_sandi' => 'required|string|min:6',
                'no_hp' => 'required|string|max:20',
            ]);
        } catch (ValidationException $e) {
            Log::debug('Register gagal: validasi error', [
                'errors' => $e->errors(),
                'input' => $request->all(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        }

        $user = User::create([
            'id' => Str::uuid()->toString(),
            'nama' => $validated['nama'],
            'email' => $validated['email'],
            'kata_sandi' => bcrypt($validated['kata_sandi']),
            'peran' => 'pemesan',
            'no_hp' => $validated['no_hp'],
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        Log::info('Register berhasil', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Registrasi berhasil',
            'data' => [
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user
            ]
        ]);
    }

    // LOGIN

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'kata_sandi' => 'required'
            ]);

            $user = User::where('email', $request->email)->first();

            if (! $user || ! Hash::check($request->kata_sandi, $user->kata_sandi)) {
                Log::debug('Login gagal: email atau password salah', [
                    'email_input' => $request->email,
                    'user_found' => $user ? true : false,
                ]);
                throw ValidationException::withMessages([
                    'email' => ['Email atau kata sandi salah.'],
                ]);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            Log::info('Login berhasil', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Login berhasil',
                'data' => [
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'user' => $user
                ]
            ]);
        } catch (ValidationException $e) {
            Log::debug('Login gagal: validasi error', [
                'errors' => $e->errors(),
                'input' => $request->all(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Login gagal',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error login: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat login',
            ], 500);
        }
    }

    // INFO USER
    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    // LOGOUT
        public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil logout',
            'data' => null,
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:pengguna,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
                'data' => null,
            ], 422);
        }

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'status' => 'success',
                'message' => 'Reset password link telah dikirim ke email Anda',
                'data' => null,
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengirim link reset password',
                'data' => null,
            ], 500);
        }
    }

        public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'kata_sandi' => bcrypt($password),
                ])->save();
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            return response()->json([
                'status' => 'success',
                'message' => 'Password berhasil direset!',
                'data' => null,
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Reset token tidak valid.',
                'data' => null,
            ], 500);
        }
    }
}
