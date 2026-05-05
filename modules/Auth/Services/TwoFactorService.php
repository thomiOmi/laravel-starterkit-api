<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\EyeFill;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Collection;
use Modules\User\Models\User;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorService
{
    public function __construct(protected Google2FA $engine) {}

    public function enable(User $user): array
    {
        $secret = $this->engine->generateSecretKey();
        $user->forceFill([
            'two_factor_secret' => encrypt($secret),
            'two_factor_recovery_codes' => encrypt(json_encode($this->generateRecoveryCodes($user))),
        ])->save();

        return [
            'secret' => $secret,
            'qr_code_svg' => $this->qrCodeSvg(config('app.name'), $user->email, $secret),
        ];
    }

    public function confirm(User $user, string $code): bool
    {
        if (! $user->two_factor_secret) {
            return false;
        }
        $secret = decrypt($user->two_factor_secret);
        if ($this->engine->verifyKey($secret, $code)) {
            $user->forceFill(['two_factor_confirmed_at' => now()])->save();

            return true;
        }

        return false;
    }

    public function disable(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }

    public function generateRecoveryCodes(User $user): array
    {
        $codes = Collection::times(8, fn () => strtolower(str()->random(10).'-'.str()->random(10)))->toArray();
        $user->forceFill(['two_factor_recovery_codes' => encrypt(json_encode($codes))])->save();

        return $codes;
    }

    public function verifyRecoveryCode(User $user, string $code): bool
    {
        if (! $user->two_factor_recovery_codes) {
            return false;
        }
        $codes = json_decode(decrypt($user->two_factor_recovery_codes), true);
        if (($key = array_search($code, $codes)) !== false) {
            unset($codes[$key]);
            $user->forceFill(['two_factor_recovery_codes' => encrypt(json_encode(array_values($codes)))])->save();

            return true;
        }

        return false;
    }

    protected function qrCodeSvg(string $company, string $holder, string $secret): string
    {
        $url = $this->engine->getQRCodeUrl($company, $holder, $secret);
        $renderer = new ImageRenderer(
            new RendererStyle(192, 0, null, null, Fill::withForegroundColor(new Rgb(255, 255, 255), new Rgb(45, 55, 72), EyeFill::inherit(), EyeFill::inherit(), EyeFill::inherit())),
            new SvgImageBackEnd
        );

        return (new Writer($renderer))->writeString($url);
    }

    public function verify(string $secret, string $code): bool
    {
        return $this->engine->verifyKey($secret, $code);
    }
}
