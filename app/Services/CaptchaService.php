<?php

declare(strict_types=1);

namespace App\Services;

class CaptchaService
{
    private const CODE_LENGTH = 5;
    private const CHARACTERS = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';

    public function generate(): array
    {
        sessionStart();
        $code = '';
        $maxIndex = strlen(self::CHARACTERS) - 1;
        for ($i = 0; $i < self::CODE_LENGTH; $i++) {
            $code .= self::CHARACTERS[random_int(0, $maxIndex)];
        }

        $_SESSION['_captcha_code'] = $code;
        $_SESSION['_captcha_expires_at'] = time() + 300;

        $svg = $this->renderSvg($code);

        return [
            'code' => $code,
            'svg'  => $svg,
        ];
    }

    public function validate(?string $input): bool
    {
        sessionStart();
        $expected = $_SESSION['_captcha_code'] ?? null;
        $expiresAt = $_SESSION['_captcha_expires_at'] ?? 0;

        // Invalidate stored captcha to prevent reuse
        unset($_SESSION['_captcha_code'], $_SESSION['_captcha_expires_at']);

        if ($expected === null || time() > $expiresAt || empty($input)) {
            return false;
        }

        return hash_equals(strtoupper((string) $expected), strtoupper(trim($input)));
    }

    public function renderSvg(string $code): string
    {
        $width = 160;
        $height = 48;

        $noise = '';
        for ($i = 0; $i < 6; $i++) {
            $x1 = random_int(0, $width);
            $y1 = random_int(0, $height);
            $x2 = random_int(0, $width);
            $y2 = random_int(0, $height);
            $color = sprintf('#%06X', random_int(0x888888, 0xCCCCCC));
            $noise .= "<line x1='{$x1}' y1='{$y1}' x2='{$x2}' y2='{$y2}' stroke='{$color}' stroke-width='1.5' />";
        }

        for ($i = 0; $i < 25; $i++) {
            $cx = random_int(0, $width);
            $cy = random_int(0, $height);
            $r = random_int(1, 2);
            $noise .= "<circle cx='{$cx}' cy='{$cy}' r='{$r}' fill='#999999' />";
        }

        $charsSvg = '';
        $charCount = strlen($code);
        $spacing = (int) (($width - 30) / $charCount);

        for ($i = 0; $i < $charCount; $i++) {
            $char = $code[$i];
            $x = 18 + ($i * $spacing) + random_int(-2, 2);
            $y = 34 + random_int(-3, 3);
            $angle = random_int(-15, 15);
            $color = sprintf('#%06X', random_int(0x111111, 0x333333));

            $charsSvg .= "<text x='{$x}' y='{$y}' font-family='Arial, sans-serif' font-size='24' font-weight='bold' fill='{$color}' transform='rotate({$angle}, {$x}, {$y})'>{$char}</text>";
        }

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}" style="background-color: #F8FAFC; border-radius: 6px; border: 1px solid #CBD5E1; user-select: none;">
    {$noise}
    {$charsSvg}
</svg>
SVG;
    }
}
