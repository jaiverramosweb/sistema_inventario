<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Security\TwoFactorService;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class TwoFactorController extends Controller
{
    public function __construct(protected TwoFactorService $twoFactorService)
    {
    }

    public function status()
    {
        $user = auth('api')->user();

        return response()->json([
            'two_factor_enabled' => (bool) $user->two_factor_enabled,
            'has_pending_setup' => ! empty($user->two_factor_pending_secret_encrypted),
            'recovery_codes_remaining' => $user->recoveryCodes()->whereNull('used_at')->count(),
        ]);
    }

    public function init()
    {
        $user = auth('api')->user();

        $secret = $this->twoFactorService->generateSecret();
        $issuer = config('app.name', 'Inventario Pro');
        $otpAuthUrl = $this->twoFactorService->buildOtpAuthUrl($issuer, $user->email, $secret);

        $renderer = new ImageRenderer(new RendererStyle(220), new SvgImageBackEnd());
        $writer = new Writer($renderer);
        $qrSvg = $writer->writeString($otpAuthUrl);

        $user->two_factor_pending_secret_encrypted = Crypt::encryptString($secret);
        $user->save();

        return response()->json([
            'manual_key' => $secret,
            'otpauth_url' => $otpAuthUrl,
            'qr_svg' => $qrSvg,
            'message' => 'Scan the QR or enter the key manually in Google Authenticator.',
        ]);
    }

    public function verifySetup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|min:6|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 422);
        }

        $user = auth('api')->user();
        if (! $user->two_factor_pending_secret_encrypted) {
            return response()->json(['error' => 'No pending 2FA setup found'], 422);
        }

        $secret = Crypt::decryptString($user->two_factor_pending_secret_encrypted);
        $matchedStep = null;
        $isValid = $this->twoFactorService->verifyCode($secret, $request->code, 1, $matchedStep);

        if (! $isValid) {
            return response()->json(['error' => 'Invalid verification code'], 422);
        }

        $user->two_factor_enabled = true;
        $user->two_factor_secret_encrypted = Crypt::encryptString($secret);
        $user->two_factor_pending_secret_encrypted = null;
        $user->two_factor_confirmed_at = now();
        $user->two_factor_last_used_step = $matchedStep;
        $user->save();

        $recoveryCodes = $this->twoFactorService->generateRecoveryCodes($user, 10);

        return response()->json([
            'message' => 'Two-factor authentication enabled successfully.',
            'two_factor_enabled' => true,
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    public function disable(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string',
            'code' => 'required|string|min:6|max:30',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 422);
        }

        $user = auth('api')->user();
        if (! Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Password is invalid'], 422);
        }

        if (! $user->two_factor_enabled || ! $user->two_factor_secret_encrypted) {
            return response()->json(['error' => 'Two-factor authentication is not active'], 422);
        }

        $secret = Crypt::decryptString($user->two_factor_secret_encrypted);
        $matchedStep = null;
        $validTotp = $this->twoFactorService->verifyCode($secret, $request->code, 1, $matchedStep);
        $validRecovery = $this->twoFactorService->consumeRecoveryCode($user, $request->code);

        if (! $validTotp && ! $validRecovery) {
            return response()->json(['error' => 'Code is invalid'], 422);
        }

        $user->recoveryCodes()->delete();
        $user->two_factor_enabled = false;
        $user->two_factor_secret_encrypted = null;
        $user->two_factor_pending_secret_encrypted = null;
        $user->two_factor_confirmed_at = null;
        $user->two_factor_last_used_step = null;
        $user->save();

        return response()->json([
            'message' => 'Two-factor authentication disabled.',
            'two_factor_enabled' => false,
        ]);
    }

    public function regenerateRecoveryCodes(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 422);
        }

        $user = auth('api')->user();
        if (! $user->two_factor_enabled) {
            return response()->json(['error' => 'Two-factor authentication is not active'], 422);
        }

        if (! Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Password is invalid'], 422);
        }

        $recoveryCodes = $this->twoFactorService->generateRecoveryCodes($user, 10);

        return response()->json([
            'message' => 'Recovery codes regenerated successfully.',
            'recovery_codes' => $recoveryCodes,
        ]);
    }
}
