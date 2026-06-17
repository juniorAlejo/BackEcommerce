<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerificationCodeMail;
use App\Models\User;
use App\Models\VerificationCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    // Paso 1: URL de redirección a Google
    public function redirect(): JsonResponse
    {
        $url = Socialite::driver('google')->stateless()->redirect()->getTargetUrl();
        return response()->json(['url' => $url]);
    }

    // Paso 2: Google regresa con el perfil
    public function callback(): \Illuminate\Http\RedirectResponse
{
    try {
        $googleUser = Socialite::driver('google')->stateless()->user();
    } catch (\Exception $e) {
        return redirect(env('FRONTEND_URL', 'http://localhost:3000') . '/auth/login?error=google');
    }

    $user = User::where('google_id', $googleUser->getId())
                ->orWhere('email', $googleUser->getEmail())
                ->first();

    // Limpiar códigos anteriores
    VerificationCode::where('email', $googleUser->getEmail())
                    ->whereIn('type', ['google_verify', 'google_data'])
                    ->delete();

    // Guardar código de verificación
    $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    VerificationCode::create([
        'email'      => $googleUser->getEmail(),
        'code'       => $code,
        'type'       => 'google_verify',
        'used'       => false,
        'expires_at' => now()->addMinutes(10),
    ]);

    // Guardar datos de Google temporalmente
    VerificationCode::create([
        'email'      => $googleUser->getEmail(),
        'code'       => json_encode([
            'google_id' => $googleUser->getId(),
            'name'      => $googleUser->getName(),
            'avatar'    => $googleUser->getAvatar(),
        ]),
        'type'       => 'google_data',
        'used'       => false,
        'expires_at' => now()->addMinutes(15),
    ]);

    Mail::to($googleUser->getEmail())->send(
        new VerificationCodeMail($code, $googleUser->getName())
    );

    $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');

    return redirect("{$frontendUrl}/auth/verify?email=" . urlencode($googleUser->getEmail()));
}

    // Paso 3: Verificar código
    public function verifyCode(Request $request): JsonResponse
{
    $data = $request->validate([
        'email' => 'required|email',
        'code'  => 'required|string|size:6',
    ]);

    $record = VerificationCode::where('email', $data['email'])
                              ->where('code', $data['code'])
                              ->where('type', 'google_verify')
                              ->where('used', false)
                              ->first();

    if (!$record || $record->isExpired()) {
        return response()->json(['message' => 'Código inválido o expirado.'], 422);
    }

    $record->update(['used' => true]);

    // Buscar datos de Google guardados
    $googleRecord = VerificationCode::where('email', $data['email'])
                                    ->where('type', 'google_data')
                                    ->latest()
                                    ->first();

    $googleData = $googleRecord ? json_decode($googleRecord->code, true) : [];

    $user = User::where('email', $data['email'])->first();

    if ($user && $user->password) {
        return response()->json([
            'status'  => 'needs_password',
            'email'   => $data['email'],
            'message' => 'Ingresa tu contraseña para continuar.',
        ]);
    }

    return response()->json([
        'status'     => 'needs_create_password',
        'email'      => $data['email'],
        'google_id'  => $googleData['google_id'] ?? '',
        'name'       => $googleData['name'] ?? '',
        'avatar'     => $googleData['avatar'] ?? '',
        'message'    => 'Crea tu contraseña para HypexTech.',
    ]);
}

    // Paso 4a: Usuario nuevo o sin contraseña → crear contraseña
    public function createPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'                => 'required|email',
            'google_id'            => 'required|string',
            'name'                 => 'required|string',
            'avatar'               => 'nullable|string',
            'password'             => 'required|string|min:8|confirmed',
        ]);

        $user = User::updateOrCreate(
            ['email' => $data['email']],
            [
                'google_id'         => $data['google_id'],
                'name'              => $data['name'],
                'avatar'            => $data['avatar'],
                'password'          => Hash::make($data['password']),
                'role'              => 'customer',
                'is_active'         => true,
                'email_verified_at' => now(),
            ]
        );

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'authenticated',
            'data'   => [
                'id'           => $user->id,
                'name'         => $user->name,
                'email'        => $user->email,
                'avatar'       => $user->avatar,
                'role'         => $user->role,
                'is_active'    => $user->is_active,
                'has_password' => true,
            ],
            'token' => $token,
        ]);
    }

    // Paso 4b: Usuario existente → verificar contraseña
    public function loginWithPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Contraseña incorrecta.'], 422);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'authenticated',
            'data'   => [
                'id'           => $user->id,
                'name'         => $user->name,
                'email'        => $user->email,
                'avatar'       => $user->avatar,
                'role'         => $user->role,
                'is_active'    => $user->is_active,
                'has_password' => true,
            ],
            'token' => $token,
        ]);
    }
}