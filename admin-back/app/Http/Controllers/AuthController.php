<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;


class AuthController extends Controller
{

    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register()
    {
        Gate::authorize('create', User::class);

        $validator = Validator::make(request()->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        $user = new User;
        $user->name = request()->name;
        $user->email = request()->email;
        $user->password = bcrypt(request()->password);
        $user->save();

        return response()->json($user, 201);
    }


    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        $credentials = request(['email', 'password']);

        $user = User::where('email', $credentials['email'] ?? null)->first();
        if (! $user || ! Hash::check($credentials['password'] ?? '', $user->password)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if ($user->two_factor_enabled) {
            $mfaToken = bin2hex(random_bytes(32));

            Cache::put(
                $this->mfaCacheKey($mfaToken),
                [
                    'user_id' => $user->id,
                    'ip' => request()->ip(),
                    'attempts' => 0,
                ],
                now()->addMinutes(5)
            );

            return response()->json([
                'requires_2fa' => true,
                'mfa_token' => $mfaToken,
                'message' => 'Two-factor authentication required.',
            ]);
        }

        if (! $token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    public function verifyTwoFactor()
    {
        $validator = Validator::make(request()->all(), [
            'mfa_token' => 'required|string',
            'code' => 'required|string|min:6|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 422);
        }

        $mfaToken = request('mfa_token');
        $challenge = Cache::get($this->mfaCacheKey($mfaToken));

        if (! $challenge) {
            return response()->json(['error' => 'MFA challenge expired'], 401);
        }

        if (($challenge['attempts'] ?? 0) >= 5) {
            Cache::forget($this->mfaCacheKey($mfaToken));
            return response()->json(['error' => 'Too many attempts'], 429);
        }

        if (($challenge['ip'] ?? null) !== request()->ip()) {
            Cache::forget($this->mfaCacheKey($mfaToken));
            return response()->json(['error' => 'Invalid MFA challenge'], 401);
        }

        $user = User::find($challenge['user_id'] ?? 0);
        if (! $user || ! $user->two_factor_enabled) {
            Cache::forget($this->mfaCacheKey($mfaToken));
            return response()->json(['error' => 'Invalid MFA challenge'], 401);
        }

        $service = app(\App\Services\Security\TwoFactorService::class);

        $secret = Crypt::decryptString($user->two_factor_secret_encrypted);
        $matchedStep = null;
        $isValid = $service->verifyCode($secret, request('code'), 1, $matchedStep);

        if (! $isValid || ($user->two_factor_last_used_step !== null && $matchedStep <= $user->two_factor_last_used_step)) {
            $challenge['attempts'] = ($challenge['attempts'] ?? 0) + 1;
            Cache::put($this->mfaCacheKey($mfaToken), $challenge, now()->addMinutes(5));
            return response()->json(['error' => 'Invalid verification code'], 422);
        }

        $user->two_factor_last_used_step = $matchedStep;
        $user->save();

        Cache::forget($this->mfaCacheKey($mfaToken));

        $token = auth('api')->login($user);
        return $this->respondWithToken($token);
    }

    public function verifyTwoFactorRecoveryCode()
    {
        $validator = Validator::make(request()->all(), [
            'mfa_token' => 'required|string',
            'recovery_code' => 'required|string|min:6|max:30',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 422);
        }

        $mfaToken = request('mfa_token');
        $challenge = Cache::get($this->mfaCacheKey($mfaToken));

        if (! $challenge) {
            return response()->json(['error' => 'MFA challenge expired'], 401);
        }

        if (($challenge['attempts'] ?? 0) >= 5) {
            Cache::forget($this->mfaCacheKey($mfaToken));
            return response()->json(['error' => 'Too many attempts'], 429);
        }

        if (($challenge['ip'] ?? null) !== request()->ip()) {
            Cache::forget($this->mfaCacheKey($mfaToken));
            return response()->json(['error' => 'Invalid MFA challenge'], 401);
        }

        $user = User::find($challenge['user_id'] ?? 0);
        if (! $user || ! $user->two_factor_enabled) {
            Cache::forget($this->mfaCacheKey($mfaToken));
            return response()->json(['error' => 'Invalid MFA challenge'], 401);
        }

        $service = app(\App\Services\Security\TwoFactorService::class);
        $consumed = $service->consumeRecoveryCode($user, request('recovery_code'));

        if (! $consumed) {
            $challenge['attempts'] = ($challenge['attempts'] ?? 0) + 1;
            Cache::put($this->mfaCacheKey($mfaToken), $challenge, now()->addMinutes(5));
            return response()->json(['error' => 'Invalid recovery code'], 422);
        }

        Cache::forget($this->mfaCacheKey($mfaToken));

        $token = auth('api')->login($user);
        return $this->respondWithToken($token);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json($this->authUserPayload(auth('api')->user()));
    }

    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'document' => 'nullable|string|max:50',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 422);
        }

        $user = auth('api')->user();

        if ($request->hasFile('image')) {
            if ($user->avatar) {
                Storage::delete($user->avatar);
            }

            $path = Storage::putFile('users', $request->file('image'));
            $user->avatar = $path;
        }

        $user->name = $request->name;
        $user->phone = $request->phone;
        $user->document = $request->document;
        $user->save();

        return response()->json([
            'message' => 'Perfil actualizado correctamente.',
            'user' => $this->authUserPayload($user),
        ]);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        $user = auth('api')->user();

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 120,
            'user' => $this->authUserPayload($user),
        ]);
    }

    protected function authUserPayload(User $user): array
    {
        $permissions = $user->getAllPermissions()->map(function ($permission) {
            return $permission->name;
        })->values();

        return [
            'id' => $user->id,
            'full_name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'document' => $user->document,
            'avatar' => $user->avatar ? env('APP_URL') . 'storage/' . $user->avatar : null,
            'sucursale_id' => $user->sucuarsal_id,
            'sucursale' => $user->sucursale?->name,
            'role' => [
                'id' => $user->role?->id,
                'name' => $user->role?->name,
            ],
            'permissions' => $permissions,
            'two_factor_enabled' => (bool) $user->two_factor_enabled,
        ];
    }

    protected function mfaCacheKey(string $mfaToken): string
    {
        return 'mfa_challenge:' . hash('sha256', $mfaToken);
    }
}
