<?php

namespace App\Services\Security;

use App\Models\User;
use App\Models\UserRecoveryCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TwoFactorService
{
    protected string $base32Alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function generateSecret(int $length = 32): string
    {
        $bytes = random_bytes(20);
        $base32 = $this->base32Encode($bytes);

        return substr($base32, 0, $length);
    }

    public function buildOtpAuthUrl(string $issuer, string $account, string $secret): string
    {
        $issuerEncoded = rawurlencode($issuer);
        $accountEncoded = rawurlencode($account);

        return "otpauth://totp/{$issuerEncoded}:{$accountEncoded}?secret={$secret}&issuer={$issuerEncoded}&algorithm=SHA1&digits=6&period=30";
    }

    public function verifyCode(string $secret, string $token, int $window = 1, ?int &$matchedStep = null): bool
    {
        $normalized = preg_replace('/\s+/', '', $token);
        if (! preg_match('/^\d{6}$/', $normalized)) {
            return false;
        }

        $currentStep = (int) floor(time() / 30);
        for ($offset = -$window; $offset <= $window; $offset++) {
            $step = $currentStep + $offset;
            $generated = $this->generateTotpForStep($secret, $step);
            if (hash_equals($generated, $normalized)) {
                $matchedStep = $step;
                return true;
            }
        }

        return false;
    }

    public function generateRecoveryCodes(User $user, int $count = 10): array
    {
        $codes = [];

        DB::transaction(function () use ($user, $count, &$codes) {
            UserRecoveryCode::where('user_id', $user->id)->delete();

            for ($index = 0; $index < $count; $index++) {
                $plainCode = strtoupper(bin2hex(random_bytes(4))) . '-' . strtoupper(bin2hex(random_bytes(4)));
                $codes[] = $plainCode;

                UserRecoveryCode::create([
                    'user_id' => $user->id,
                    'code_hash' => Hash::make($plainCode),
                ]);
            }
        });

        return $codes;
    }

    public function consumeRecoveryCode(User $user, string $recoveryCode): bool
    {
        $normalized = strtoupper(trim($recoveryCode));
        $codes = UserRecoveryCode::where('user_id', $user->id)
            ->whereNull('used_at')
            ->get();

        foreach ($codes as $code) {
            if (! Hash::check($normalized, $code->code_hash)) {
                continue;
            }

            $code->used_at = now();
            $code->save();
            return true;
        }

        return false;
    }

    protected function generateTotpForStep(string $secret, int $timeStep, int $digits = 6): string
    {
        $secretBinary = $this->base32Decode($secret);

        $binaryStep = pack('N*', 0) . pack('N*', $timeStep);
        $hash = hash_hmac('sha1', $binaryStep, $secretBinary, true);

        $offset = ord(substr($hash, -1)) & 0x0F;
        $segment = substr($hash, $offset, 4);

        $value = unpack('N', $segment)[1] & 0x7FFFFFFF;
        $mod = 10 ** $digits;

        return str_pad((string) ($value % $mod), $digits, '0', STR_PAD_LEFT);
    }

    protected function base32Encode(string $binary): string
    {
        $bits = '';
        $encoded = '';

        foreach (str_split($binary) as $character) {
            $bits .= str_pad(decbin(ord($character)), 8, '0', STR_PAD_LEFT);
        }

        $chunks = str_split($bits, 5);
        foreach ($chunks as $chunk) {
            if (strlen($chunk) < 5) {
                $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            }
            $encoded .= $this->base32Alphabet[bindec($chunk)];
        }

        return $encoded;
    }

    protected function base32Decode(string $base32): string
    {
        $clean = strtoupper(preg_replace('/[^A-Z2-7]/', '', $base32));
        $bits = '';

        foreach (str_split($clean) as $character) {
            $position = strpos($this->base32Alphabet, $character);
            if ($position === false) {
                continue;
            }
            $bits .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
        }

        $binary = '';
        $bytes = str_split($bits, 8);
        foreach ($bytes as $byte) {
            if (strlen($byte) < 8) {
                continue;
            }
            $binary .= chr(bindec($byte));
        }

        return $binary;
    }
}
