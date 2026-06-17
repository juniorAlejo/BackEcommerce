<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerificationCodeMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class PasswordResetController extends Controller
{
    // Paso 1: Enviar código de 6 dígitos al correo
    public function sendLink(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if ($user && is_null($user->password) && !is_null($user->google_id)) {
            return response()->json([
                'message' => 'Esta cuenta usa Google. Inicia sesión con Google.',
            ], 422);
        }

        // Generar código de 6 dígitos
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Guardar código en la tabla (guardamos el código en texto plano para poder verificarlo)
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            ['token' => $code, 'created_at' => now()]
        );

        if ($user) {
            Mail::to($request->email)->send(
                new VerificationCodeMail($code, $user->name)
            );
        }

        return response()->json(['message' => 'Código enviado si el correo existe.']);
    }

    // Paso 2: Verificar código
    public function verifyCode(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'code'  => 'required|string|size:6',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->code)
            ->first();

        if (!$record) {
            return response()->json(['message' => 'Código inválido o expirado.'], 422);
        }

        // Verificar que no haya expirado (10 minutos)
        if (now()->diffInMinutes($record->created_at) > 10) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json(['message' => 'El código ha expirado. Solicita uno nuevo.'], 422);
        }

        // Devolver el código como token para el siguiente paso
        return response()->json([
            'token'   => $request->code,
            'message' => 'Código verificado correctamente.',
        ]);
    }

    // Paso 3: Restablecer contraseña
    public function reset(Request $request): JsonResponse
    {
        $request->validate([
            'email'                => 'required|email',
            'token'                => 'required|string',
            'password'             => 'required|string|min:8|confirmed',
        ]);

        // Verificar que el token (código) sea válido
        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        if (!$record) {
            return response()->json(['message' => 'Token inválido o expirado.'], 422);
        }

        // Verificar expiración
        if (now()->diffInMinutes($record->created_at) > 10) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json(['message' => 'El código ha expirado.'], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado.'], 422);
        }

        // Actualizar contraseña
        $user->update(['password' => Hash::make($request->password)]);

        // Eliminar token usado
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        // Cerrar todas las sesiones activas
        $user->tokens()->delete();

        return response()->json(['message' => 'Contraseña restablecida correctamente.']);
    }
}