<?php

/**
 * awColor - the ultimate colour management library for PHP.
 *
 * Author:  Mark Walker <https://awcode.com>
 * License: MIT.
 */

declare(strict_types=1);

namespace AWcode;

use InvalidArgumentException;
use Stringable;

class awColor implements Stringable
{
    /** Six-digit lower-case hex string without leading hash. */
    private string $_hex = '000000';

    /** @var array{0:int,1:int,2:int} RGB triplet, each component clamped to 0-255. */
    private array $_rgb = [0, 0, 0];

    /** @var array{0:int,1:float,2:float} HSL triplet: hue 0-360, saturation/luminosity 0-1. */
    private array $_hsl = [0, 0.0, 0.0];

    /** Alpha channel 0-1. */
    private float $_alpha = 1.0;

    /**
     * Construct a color.
     *
     * Backwards-compatible signature:
     *   - `new awColor('#ff0000')`  hex string
     *   - `new awColor(255, 0, 0)`  RGB
     *   - `new awColor(0, 1, 0.5, true)`  HSL
     *
     * The first argument may also be a CSS named color (`'red'`), an
     * `rgb()`/`rgba()`/`hsl()`/`hsla()` string, or a 3/4/6/8-digit hex.
     */
    public function __construct(
        string|int|float $HexRH,
        bool|int|float $BS = false,
        bool|int|float $GL = false,
        bool|int|float $hsl = false,
    ) {
        if (is_string($HexRH) && $BS === false && $GL === false && $hsl === false) {
            $this->setFromString($HexRH);
            return;
        }

        if ($hsl === false || $hsl === 0) {
            $this->setRgb((int) round((float) $HexRH), (int) round((float) $BS), (int) round((float) $GL));
            return;
        }

        $this->setHsl((float) $HexRH, (float) $BS, (float) $GL);
    }

    // ---------------------------------------------------------------------
    // Static factory helpers
    // ---------------------------------------------------------------------

    public static function fromHex(string $hex): self
    {
        $color = new self('#000000');
        $color->setHex($hex);
        return $color;
    }

    public static function fromRgb(int $r, int $g, int $b, float $alpha = 1.0): self
    {
        $color = new self($r, $g, $b);
        $color->setAlpha($alpha);
        return $color;
    }

    public static function fromHsl(float $h, float $s, float $l, float $alpha = 1.0): self
    {
        $color = new self($h, $s, $l, true);
        $color->setAlpha($alpha);
        return $color;
    }

    public static function fromHsv(float $h, float $s, float $v): self
    {
        return self::fromRgb(...self::hsvToRgb([$h, $s, $v]));
    }

    public static function fromCmyk(float $c, float $m, float $y, float $k): self
    {
        return self::fromRgb(...self::cmykToRgb([$c, $m, $y, $k]));
    }

    public static function fromName(string $name): self
    {
        $key = strtolower(trim($name));
        if (!isset(self::NAMED_COLORS[$key])) {
            throw new InvalidArgumentException("Unknown CSS color name: {$name}");
        }

        return self::fromHex(self::NAMED_COLORS[$key]);
    }

    public static function fromString(string $color): self
    {
        $instance = new self('#000000');
        $instance->setFromString($color);
        return $instance;
    }

    public static function random(): self
    {
        return self::fromRgb(random_int(0, 255), random_int(0, 255), random_int(0, 255));
    }

    // ---------------------------------------------------------------------
    // Static conversion helpers (backwards-compatible)
    // ---------------------------------------------------------------------

    public static function formatHex(string $hex): string
    {
        $hex = ltrim(trim($hex), '#');

        if (strlen($hex) === 3 && ctype_xdigit($hex)) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        } elseif (strlen($hex) === 4 && ctype_xdigit($hex)) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2].$hex[3].$hex[3];
        }

        if (strlen($hex) === 8 && ctype_xdigit($hex)) {
            // Strip alpha to keep BC for callers that expect 6 chars.
            $hex = substr($hex, 0, 6);
        }

        if (ctype_xdigit($hex) && strlen($hex) === 6) {
            return strtolower($hex);
        }

        return '000000';
    }

    /**
     * @param array{0:int|float,1:int|float,2:int|float} $rgb
     */
    public static function rgbToHex(array $rgb = []): string
    {
        if (count($rgb) !== 3) {
            throw new InvalidArgumentException('rgbToHex expects an array of 3 components.');
        }

        $r = self::clampByte($rgb[0]);
        $g = self::clampByte($rgb[1]);
        $b = self::clampByte($rgb[2]);

        return sprintf('%02x%02x%02x', $r, $g, $b);
    }

    /**
     * @return array{0:int,1:int,2:int}
     */
    public static function hexToRgb(string $hex): array
    {
        $hex = self::formatHex($hex);

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * @param array{0:int|float,1:int|float,2:int|float} $rgb
     * @return array{0:int,1:float,2:float}
     */
    public static function rgbToHsl(array $rgb = []): array
    {
        [$r, $g, $b] = $rgb;
        $r = self::clampByte($r) / 255;
        $g = self::clampByte($g) / 255;
        $b = self::clampByte($b) / 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $chroma = $max - $min;

        $luminosity = ($max + $min) / 2;
        $hue = 0.0;
        $saturation = 0.0;

        if ($chroma > 0.0) {
            $saturation = $chroma / (1 - abs(2 * $luminosity - 1));
            if ($max === $r) {
                $hue = 60 * fmod((($g - $b) / $chroma), 6);
                if ($b > $g) {
                    $hue += 360;
                }
            } elseif ($max === $g) {
                $hue = 60 * (($b - $r) / $chroma + 2);
            } else {
                $hue = 60 * (($r - $g) / $chroma + 4);
            }
        }

        return [
            (int) round($hue),
            round($saturation, 4),
            round($luminosity, 4),
        ];
    }

    /**
     * Convert HSL to RGB. **Returns integers in the 0-255 range** (fixes the
     * historical bug that produced 0-1 floats and broke `rgbToHex`).
     *
     * @param array{0:int|float,1:int|float,2:int|float} $hsl
     * @return array{0:int,1:int,2:int}
     */
    public static function hslToRgb(array $hsl = []): array
    {
        [$hue, $saturation, $luminosity] = $hsl;
        $hue = fmod(fmod((float) $hue, 360) + 360, 360);
        $hue = $hue / 360;
        $saturation = max(0.0, min(1.0, (float) $saturation));
        $luminosity = max(0.0, min(1.0, (float) $luminosity));

        if ($saturation == 0.0) {
            $value = $luminosity;
            return [
                (int) round($value * 255),
                (int) round($value * 255),
                (int) round($value * 255),
            ];
        }

        $chroma = (1 - abs(2 * $luminosity - 1)) * $saturation;
        $h_ = $hue * 6;
        $x = $chroma * (1 - abs((fmod($h_, 2)) - 1));
        $m = $luminosity - $chroma / 2;

        $r = $g = $b = 0.0;
        if ($h_ < 1) {
            [$r, $g, $b] = [$chroma, $x, 0.0];
        } elseif ($h_ < 2) {
            [$r, $g, $b] = [$x, $chroma, 0.0];
        } elseif ($h_ < 3) {
            [$r, $g, $b] = [0.0, $chroma, $x];
        } elseif ($h_ < 4) {
            [$r, $g, $b] = [0.0, $x, $chroma];
        } elseif ($h_ < 5) {
            [$r, $g, $b] = [$x, 0.0, $chroma];
        } else {
            [$r, $g, $b] = [$chroma, 0.0, $x];
        }

        return [
            (int) round(($r + $m) * 255),
            (int) round(($g + $m) * 255),
            (int) round(($b + $m) * 255),
        ];
    }

    /**
     * @return array{0:int,1:float,2:float}
     */
    public static function hexToHsl(string $hex): array
    {
        return self::rgbToHsl(self::hexToRgb($hex));
    }

    /**
     * @param array{0:int|float,1:int|float,2:int|float} $hsl
     */
    public static function hslToHex(array $hsl = []): string
    {
        return self::rgbToHex(self::hslToRgb($hsl));
    }

    /**
     * @param array{0:int|float,1:int|float,2:int|float} $rgb
     * @return array{0:int,1:float,2:float} hue 0-360, saturation/value 0-1
     */
    public static function rgbToHsv(array $rgb): array
    {
        [$r, $g, $b] = $rgb;
        $r = self::clampByte($r) / 255;
        $g = self::clampByte($g) / 255;
        $b = self::clampByte($b) / 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $chroma = $max - $min;

        $hue = 0.0;
        $saturation = $max == 0.0 ? 0.0 : $chroma / $max;
        $value = $max;

        if ($chroma > 0.0) {
            if ($max === $r) {
                $hue = 60 * fmod((($g - $b) / $chroma), 6);
                if ($b > $g) {
                    $hue += 360;
                }
            } elseif ($max === $g) {
                $hue = 60 * (($b - $r) / $chroma + 2);
            } else {
                $hue = 60 * (($r - $g) / $chroma + 4);
            }
        }

        return [(int) round($hue), round($saturation, 4), round($value, 4)];
    }

    /**
     * @param array{0:int|float,1:int|float,2:int|float} $hsv
     * @return array{0:int,1:int,2:int}
     */
    public static function hsvToRgb(array $hsv): array
    {
        [$h, $s, $v] = $hsv;
        $h = fmod(fmod((float) $h, 360) + 360, 360);
        $s = max(0.0, min(1.0, (float) $s));
        $v = max(0.0, min(1.0, (float) $v));

        $c = $v * $s;
        $hh = $h / 60;
        $x = $c * (1 - abs(fmod($hh, 2) - 1));
        $m = $v - $c;

        if ($hh < 1) { [$r, $g, $b] = [$c, $x, 0.0]; }
        elseif ($hh < 2) { [$r, $g, $b] = [$x, $c, 0.0]; }
        elseif ($hh < 3) { [$r, $g, $b] = [0.0, $c, $x]; }
        elseif ($hh < 4) { [$r, $g, $b] = [0.0, $x, $c]; }
        elseif ($hh < 5) { [$r, $g, $b] = [$x, 0.0, $c]; }
        else             { [$r, $g, $b] = [$c, 0.0, $x]; }

        return [
            (int) round(($r + $m) * 255),
            (int) round(($g + $m) * 255),
            (int) round(($b + $m) * 255),
        ];
    }

    /**
     * @param array{0:int|float,1:int|float,2:int|float} $rgb
     * @return array{0:float,1:float,2:float,3:float} cmyk in 0-1 range
     */
    public static function rgbToCmyk(array $rgb): array
    {
        [$r, $g, $b] = $rgb;
        $r = self::clampByte($r) / 255;
        $g = self::clampByte($g) / 255;
        $b = self::clampByte($b) / 255;

        $k = 1 - max($r, $g, $b);
        if ($k >= 1.0) {
            return [0.0, 0.0, 0.0, 1.0];
        }

        return [
            round((1 - $r - $k) / (1 - $k), 4),
            round((1 - $g - $k) / (1 - $k), 4),
            round((1 - $b - $k) / (1 - $k), 4),
            round($k, 4),
        ];
    }

    /**
     * @param array{0:int|float,1:int|float,2:int|float,3:int|float} $cmyk in 0-1 range
     * @return array{0:int,1:int,2:int}
     */
    public static function cmykToRgb(array $cmyk): array
    {
        [$c, $m, $y, $k] = $cmyk;
        $c = max(0.0, min(1.0, (float) $c));
        $m = max(0.0, min(1.0, (float) $m));
        $y = max(0.0, min(1.0, (float) $y));
        $k = max(0.0, min(1.0, (float) $k));

        return [
            (int) round(255 * (1 - $c) * (1 - $k)),
            (int) round(255 * (1 - $m) * (1 - $k)),
            (int) round(255 * (1 - $y) * (1 - $k)),
        ];
    }

    // ---------------------------------------------------------------------
    // Backwards-compatible instance accessors
    // ---------------------------------------------------------------------

    public function getHex(): string
    {
        return $this->_hex;
    }

    public function getHexString(bool $withHash = true): string
    {
        return ($withHash ? '#' : '').$this->_hex;
    }

    /** @return array{0:int,1:int,2:int} */
    public function getRgb(): array
    {
        return $this->_rgb;
    }

    public function getR(): int { return $this->_rgb[0]; }
    public function getG(): int { return $this->_rgb[1]; }
    public function getB(): int { return $this->_rgb[2]; }

    /** @return array{0:int,1:float,2:float} */
    public function getHsl(): array
    {
        return $this->_hsl;
    }

    public function getH(): int { return $this->_hsl[0]; }
    public function getS(): float { return $this->_hsl[1]; }
    public function getL(): float { return $this->_hsl[2]; }

    public function getAlpha(): float
    {
        return $this->_alpha;
    }

    /** @return array{0:int,1:float,2:float} */
    public function getHsv(): array
    {
        return self::rgbToHsv($this->_rgb);
    }

    /** @return array{0:float,1:float,2:float,3:float} */
    public function getCmyk(): array
    {
        return self::rgbToCmyk($this->_rgb);
    }

    public function getRgbString(): string
    {
        return sprintf('rgb(%d, %d, %d)', $this->_rgb[0], $this->_rgb[1], $this->_rgb[2]);
    }

    public function getRgbaString(): string
    {
        return sprintf(
            'rgba(%d, %d, %d, %s)',
            $this->_rgb[0], $this->_rgb[1], $this->_rgb[2],
            self::formatFloat($this->_alpha),
        );
    }

    public function getHslString(): string
    {
        return sprintf(
            'hsl(%d, %d%%, %d%%)',
            $this->_hsl[0],
            (int) round($this->_hsl[1] * 100),
            (int) round($this->_hsl[2] * 100),
        );
    }

    public function getHslaString(): string
    {
        return sprintf(
            'hsla(%d, %d%%, %d%%, %s)',
            $this->_hsl[0],
            (int) round($this->_hsl[1] * 100),
            (int) round($this->_hsl[2] * 100),
            self::formatFloat($this->_alpha),
        );
    }

    private static function formatFloat(float $value): string
    {
        return rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.') ?: '0';
    }

    public function setRgb(int $r, int $g, int $b): self
    {
        $this->_rgb = [self::clampByte($r), self::clampByte($g), self::clampByte($b)];
        $this->_hex = self::rgbToHex($this->_rgb);
        $this->_hsl = self::rgbToHsl($this->_rgb);
        return $this;
    }

    public function setHex(string $hex): self
    {
        $this->_hex = self::formatHex($hex);
        $this->_rgb = self::hexToRgb($this->_hex);
        $this->_hsl = self::rgbToHsl($this->_rgb);
        return $this;
    }

    public function setHsl(float $h, float $s, float $l): self
    {
        $this->_hsl = [(int) round($h), $s, $l];
        $this->_rgb = self::hslToRgb($this->_hsl);
        $this->_hex = self::rgbToHex($this->_rgb);
        return $this;
    }

    public function setR(int $r): self { return $this->setRgb($r, $this->_rgb[1], $this->_rgb[2]); }
    public function setG(int $g): self { return $this->setRgb($this->_rgb[0], $g, $this->_rgb[2]); }
    public function setB(int $b): self { return $this->setRgb($this->_rgb[0], $this->_rgb[1], $b); }

    public function setAlpha(float $alpha): self
    {
        $this->_alpha = max(0.0, min(1.0, $alpha));
        return $this;
    }

    // ---------------------------------------------------------------------
    // Brightness / contrast helpers
    // ---------------------------------------------------------------------

    /**
     * @param array{0:int,1:int,2:int}|false $color
     */
    public function isLight(array|false $color = false, int $contrastLimit = 130): bool
    {
        $rgb = $color ?: $this->_rgb;

        $contrast = (
            $rgb[0] * $rgb[0] * .299 +
            $rgb[1] * $rgb[1] * .587 +
            $rgb[2] * $rgb[2] * .114
        );

        return $contrast > pow($contrastLimit, 2);
    }

    /**
     * @param array{0:int,1:int,2:int}|false $color
     */
    public function isDark(array|false $color = false, int $contrastLimit = 130): bool
    {
        return !$this->isLight($color, $contrastLimit);
    }

    /**
     * @param array{0:int,1:int,2:int}|false $color
     */
    public function isGrey(array|false $color = false): bool
    {
        $rgb = $color ?: $this->_rgb;
        return max($rgb) === min($rgb);
    }

    /**
     * Alias of {@see isGrey()} for US-English consumers.
     *
     * @param array{0:int,1:int,2:int}|false $color
     */
    public function isGray(array|false $color = false): bool
    {
        return $this->isGrey($color);
    }

    /**
     * Returns the complementary color as an RGB triplet (0-255 ints).
     *
     * @return array{0:int,1:int,2:int}
     */
    public function complementary(int $hueShift = 180): array
    {
        [$hue, $saturation, $luminosity] = $this->_hsl;
        $newHue = ($hue + $hueShift) % 360;
        if ($newHue < 0) {
            $newHue += 360;
        }

        return self::hslToRgb([$newHue, $saturation, $luminosity]);
    }

    // ---------------------------------------------------------------------
    // Immutable manipulation (returns new awColor instances; chainable)
    // ---------------------------------------------------------------------

    public function lighten(float $amount): self
    {
        [$h, $s, $l] = $this->_hsl;
        return self::fromHsl((float) $h, $s, max(0.0, min(1.0, $l + $amount)), $this->_alpha);
    }

    public function darken(float $amount): self
    {
        return $this->lighten(-$amount);
    }

    public function saturate(float $amount): self
    {
        [$h, $s, $l] = $this->_hsl;
        return self::fromHsl((float) $h, max(0.0, min(1.0, $s + $amount)), $l, $this->_alpha);
    }

    public function desaturate(float $amount): self
    {
        return $this->saturate(-$amount);
    }

    public function rotate(float $degrees): self
    {
        [$h, $s, $l] = $this->_hsl;
        return self::fromHsl((float) $h + $degrees, $s, $l, $this->_alpha);
    }

    public function complement(int $hueShift = 180): self
    {
        return $this->rotate((float) $hueShift);
    }

    public function invert(): self
    {
        $color = self::fromRgb(255 - $this->_rgb[0], 255 - $this->_rgb[1], 255 - $this->_rgb[2]);
        $color->setAlpha($this->_alpha);
        return $color;
    }

    public function grayscale(): self
    {
        $luma = (int) round(
            0.299 * $this->_rgb[0] + 0.587 * $this->_rgb[1] + 0.114 * $this->_rgb[2]
        );
        $color = self::fromRgb($luma, $luma, $luma);
        $color->setAlpha($this->_alpha);
        return $color;
    }

    /** Mix the receiver with another color (weight = share of $other, 0-1). */
    public function mix(self $other, float $weight = 0.5): self
    {
        $weight = max(0.0, min(1.0, $weight));
        $w = 1 - $weight;
        $color = self::fromRgb(
            (int) round($this->_rgb[0] * $w + $other->_rgb[0] * $weight),
            (int) round($this->_rgb[1] * $w + $other->_rgb[1] * $weight),
            (int) round($this->_rgb[2] * $w + $other->_rgb[2] * $weight),
        );
        $color->setAlpha($this->_alpha * $w + $other->_alpha * $weight);
        return $color;
    }

    public function tint(float $amount = 0.1): self
    {
        return $this->mix(self::fromRgb(255, 255, 255), $amount);
    }

    public function shade(float $amount = 0.1): self
    {
        return $this->mix(self::fromRgb(0, 0, 0), $amount);
    }

    public function fadeIn(float $amount): self
    {
        $color = clone $this;
        $color->setAlpha($this->_alpha + $amount);
        return $color;
    }

    public function fadeOut(float $amount): self
    {
        return $this->fadeIn(-$amount);
    }

    // ---------------------------------------------------------------------
    // Color schemes
    // ---------------------------------------------------------------------

    /** @return array{0:self,1:self,2:self} */
    public function triadic(): array
    {
        return [clone $this, $this->rotate(120), $this->rotate(240)];
    }

    /** @return array{0:self,1:self,2:self,3:self} */
    public function tetradic(): array
    {
        return [clone $this, $this->rotate(90), $this->rotate(180), $this->rotate(270)];
    }

    /** @return array{0:self,1:self,2:self} */
    public function splitComplementary(): array
    {
        return [clone $this, $this->rotate(150), $this->rotate(210)];
    }

    /**
     * Evenly distributed analogous palette.
     *
     * @return self[]
     */
    public function analogous(int $count = 3, float $angle = 30.0): array
    {
        $count = max(1, $count);
        $palette = [];
        $start = -(($count - 1) / 2) * $angle;
        for ($i = 0; $i < $count; $i++) {
            $palette[] = $this->rotate($start + $i * $angle);
        }
        return $palette;
    }

    /**
     * Monochromatic palette, varying luminosity from dark to light.
     *
     * @return self[]
     */
    public function monochromatic(int $count = 5): array
    {
        $count = max(1, $count);
        if ($count === 1) {
            return [clone $this];
        }
        $palette = [];
        for ($i = 0; $i < $count; $i++) {
            $l = $count === 1 ? $this->_hsl[2] : $i / ($count - 1);
            $palette[] = self::fromHsl((float) $this->_hsl[0], $this->_hsl[1], $l, $this->_alpha);
        }
        return $palette;
    }

    // ---------------------------------------------------------------------
    // WCAG accessibility helpers
    // ---------------------------------------------------------------------

    /** WCAG 2.x relative luminance (0-1). */
    public function luminance(): float
    {
        $channel = static function (int $value): float {
            $c = $value / 255;
            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        };

        return 0.2126 * $channel($this->_rgb[0])
             + 0.7152 * $channel($this->_rgb[1])
             + 0.0722 * $channel($this->_rgb[2]);
    }

    /** Perceived brightness (0-255), Rec. 601 weighting. */
    public function brightness(): float
    {
        return (
            $this->_rgb[0] * 0.299
            + $this->_rgb[1] * 0.587
            + $this->_rgb[2] * 0.114
        );
    }

    public function contrastRatio(self $other): float
    {
        $a = $this->luminance();
        $b = $other->luminance();
        $light = max($a, $b);
        $dark = min($a, $b);
        return ($light + 0.05) / ($dark + 0.05);
    }

    /**
     * Check if the receiver paired with $background passes WCAG.
     *
     * @param 'AA'|'AAA' $level
     * @param 'normal'|'large' $size
     */
    public function isAccessible(self $background, string $level = 'AA', string $size = 'normal'): bool
    {
        $ratio = $this->contrastRatio($background);
        return $ratio >= match ([$level, $size]) {
            ['AAA', 'normal'] => 7.0,
            ['AAA', 'large']  => 4.5,
            ['AA',  'large']  => 3.0,
            default           => 4.5,
        };
    }

    public function pickReadable(self ...$candidates): self
    {
        if ($candidates === []) {
            $candidates = [self::fromRgb(0, 0, 0), self::fromRgb(255, 255, 255)];
        }
        $best = $candidates[0];
        $bestRatio = $this->contrastRatio($best);
        foreach (array_slice($candidates, 1) as $candidate) {
            $ratio = $this->contrastRatio($candidate);
            if ($ratio > $bestRatio) {
                $best = $candidate;
                $bestRatio = $ratio;
            }
        }
        return $best;
    }

    // ---------------------------------------------------------------------
    // Comparison and serialization
    // ---------------------------------------------------------------------

    public function equals(self $other): bool
    {
        return $this->_rgb === $other->_rgb && abs($this->_alpha - $other->_alpha) < 1e-9;
    }

    /** Euclidean distance between two RGB colors (0-441.67). */
    public function distance(self $other): float
    {
        return sqrt(
            ($this->_rgb[0] - $other->_rgb[0]) ** 2
            + ($this->_rgb[1] - $other->_rgb[1]) ** 2
            + ($this->_rgb[2] - $other->_rgb[2]) ** 2
        );
    }

    /**
     * @return array{hex:string,rgb:array{0:int,1:int,2:int},hsl:array{0:int,1:float,2:float},alpha:float}
     */
    public function toArray(): array
    {
        return [
            'hex' => $this->_hex,
            'rgb' => $this->_rgb,
            'hsl' => $this->_hsl,
            'alpha' => $this->_alpha,
        ];
    }

    public function toJson(int $flags = 0): string
    {
        return json_encode($this->toArray(), $flags | JSON_THROW_ON_ERROR);
    }

    // ---------------------------------------------------------------------
    // Internal utilities
    // ---------------------------------------------------------------------

    private static function clampByte(int|float $value): int
    {
        $value = (int) round((float) $value);
        return max(0, min(255, $value));
    }

    /**
     * Parse a flexible color string (named, hex, rgb(), rgba(), hsl(), hsla()).
     */
    private function setFromString(string $input): void
    {
        $value = trim($input);
        $key = strtolower($value);

        if (isset(self::NAMED_COLORS[$key])) {
            $this->setHex(self::NAMED_COLORS[$key]);
            return;
        }

        if ($value === 'transparent') {
            $this->setHex('000000');
            $this->setAlpha(0.0);
            return;
        }

        if ($value !== '' && ($value[0] === '#' || ctype_xdigit($value))) {
            $hex = ltrim($value, '#');
            if (in_array(strlen($hex), [3, 4, 6, 8], true) && ctype_xdigit($hex)) {
                if (strlen($hex) === 4) {
                    $alphaHex = $hex[3].$hex[3];
                    $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
                    $this->setHex($hex);
                    $this->setAlpha(hexdec($alphaHex) / 255);
                    return;
                }
                if (strlen($hex) === 8) {
                    $alphaHex = substr($hex, 6, 2);
                    $this->setHex(substr($hex, 0, 6));
                    $this->setAlpha(hexdec($alphaHex) / 255);
                    return;
                }
                $this->setHex($hex);
                return;
            }
        }

        if (preg_match('/^rgba?\(([^)]+)\)$/i', $value, $m)) {
            $parts = preg_split('/[,\s\/]+/', trim($m[1])) ?: [];
            $parts = array_values(array_filter($parts, static fn ($p) => $p !== ''));
            if (count($parts) >= 3) {
                $this->setRgb(
                    self::parseByte($parts[0]),
                    self::parseByte($parts[1]),
                    self::parseByte($parts[2]),
                );
                if (isset($parts[3])) {
                    $this->setAlpha(self::parseAlpha($parts[3]));
                }
                return;
            }
        }

        if (preg_match('/^hsla?\(([^)]+)\)$/i', $value, $m)) {
            $parts = preg_split('/[,\s\/]+/', trim($m[1])) ?: [];
            $parts = array_values(array_filter($parts, static fn ($p) => $p !== ''));
            if (count($parts) >= 3) {
                $this->setHsl(
                    self::parseHue($parts[0]),
                    self::parsePercent($parts[1]),
                    self::parsePercent($parts[2]),
                );
                if (isset($parts[3])) {
                    $this->setAlpha(self::parseAlpha($parts[3]));
                }
                return;
            }
        }

        // Fall back to hex parsing of the raw string (handles cases like '00ff00').
        $this->setHex($value);
    }

    private static function parseByte(string $value): int
    {
        $value = trim($value);
        if (str_ends_with($value, '%')) {
            return (int) round(((float) rtrim($value, '%')) * 255 / 100);
        }
        return (int) round((float) $value);
    }

    private static function parseAlpha(string $value): float
    {
        $value = trim($value);
        if (str_ends_with($value, '%')) {
            return ((float) rtrim($value, '%')) / 100;
        }
        return (float) $value;
    }

    private static function parsePercent(string $value): float
    {
        $value = trim($value);
        if (str_ends_with($value, '%')) {
            return ((float) rtrim($value, '%')) / 100;
        }
        $float = (float) $value;
        // Accept either 0-1 or 0-100 input.
        return $float > 1.0 ? $float / 100 : $float;
    }

    private static function parseHue(string $value): float
    {
        $value = trim($value);
        if (str_ends_with($value, 'deg')) {
            return (float) substr($value, 0, -3);
        }
        if (str_ends_with($value, 'rad')) {
            return (float) substr($value, 0, -3) * 180 / M_PI;
        }
        if (str_ends_with($value, 'turn')) {
            return (float) substr($value, 0, -4) * 360;
        }
        return (float) $value;
    }

    public function __toString(): string
    {
        return $this->getHexString();
    }

    /**
     * CSS3 / CSS4 named colors, mapped to 6-digit lower-case hex.
     */
    private const NAMED_COLORS = [
        'aliceblue' => 'f0f8ff',
        'antiquewhite' => 'faebd7',
        'aqua' => '00ffff',
        'aquamarine' => '7fffd4',
        'azure' => 'f0ffff',
        'beige' => 'f5f5dc',
        'bisque' => 'ffe4c4',
        'black' => '000000',
        'blanchedalmond' => 'ffebcd',
        'blue' => '0000ff',
        'blueviolet' => '8a2be2',
        'brown' => 'a52a2a',
        'burlywood' => 'deb887',
        'cadetblue' => '5f9ea0',
        'chartreuse' => '7fff00',
        'chocolate' => 'd2691e',
        'coral' => 'ff7f50',
        'cornflowerblue' => '6495ed',
        'cornsilk' => 'fff8dc',
        'crimson' => 'dc143c',
        'cyan' => '00ffff',
        'darkblue' => '00008b',
        'darkcyan' => '008b8b',
        'darkgoldenrod' => 'b8860b',
        'darkgray' => 'a9a9a9',
        'darkgrey' => 'a9a9a9',
        'darkgreen' => '006400',
        'darkkhaki' => 'bdb76b',
        'darkmagenta' => '8b008b',
        'darkolivegreen' => '556b2f',
        'darkorange' => 'ff8c00',
        'darkorchid' => '9932cc',
        'darkred' => '8b0000',
        'darksalmon' => 'e9967a',
        'darkseagreen' => '8fbc8f',
        'darkslateblue' => '483d8b',
        'darkslategray' => '2f4f4f',
        'darkslategrey' => '2f4f4f',
        'darkturquoise' => '00ced1',
        'darkviolet' => '9400d3',
        'deeppink' => 'ff1493',
        'deepskyblue' => '00bfff',
        'dimgray' => '696969',
        'dimgrey' => '696969',
        'dodgerblue' => '1e90ff',
        'firebrick' => 'b22222',
        'floralwhite' => 'fffaf0',
        'forestgreen' => '228b22',
        'fuchsia' => 'ff00ff',
        'gainsboro' => 'dcdcdc',
        'ghostwhite' => 'f8f8ff',
        'gold' => 'ffd700',
        'goldenrod' => 'daa520',
        'gray' => '808080',
        'grey' => '808080',
        'green' => '008000',
        'greenyellow' => 'adff2f',
        'honeydew' => 'f0fff0',
        'hotpink' => 'ff69b4',
        'indianred' => 'cd5c5c',
        'indigo' => '4b0082',
        'ivory' => 'fffff0',
        'khaki' => 'f0e68c',
        'lavender' => 'e6e6fa',
        'lavenderblush' => 'fff0f5',
        'lawngreen' => '7cfc00',
        'lemonchiffon' => 'fffacd',
        'lightblue' => 'add8e6',
        'lightcoral' => 'f08080',
        'lightcyan' => 'e0ffff',
        'lightgoldenrodyellow' => 'fafad2',
        'lightgray' => 'd3d3d3',
        'lightgrey' => 'd3d3d3',
        'lightgreen' => '90ee90',
        'lightpink' => 'ffb6c1',
        'lightsalmon' => 'ffa07a',
        'lightseagreen' => '20b2aa',
        'lightskyblue' => '87cefa',
        'lightslategray' => '778899',
        'lightslategrey' => '778899',
        'lightsteelblue' => 'b0c4de',
        'lightyellow' => 'ffffe0',
        'lime' => '00ff00',
        'limegreen' => '32cd32',
        'linen' => 'faf0e6',
        'magenta' => 'ff00ff',
        'maroon' => '800000',
        'mediumaquamarine' => '66cdaa',
        'mediumblue' => '0000cd',
        'mediumorchid' => 'ba55d3',
        'mediumpurple' => '9370db',
        'mediumseagreen' => '3cb371',
        'mediumslateblue' => '7b68ee',
        'mediumspringgreen' => '00fa9a',
        'mediumturquoise' => '48d1cc',
        'mediumvioletred' => 'c71585',
        'midnightblue' => '191970',
        'mintcream' => 'f5fffa',
        'mistyrose' => 'ffe4e1',
        'moccasin' => 'ffe4b5',
        'navajowhite' => 'ffdead',
        'navy' => '000080',
        'oldlace' => 'fdf5e6',
        'olive' => '808000',
        'olivedrab' => '6b8e23',
        'orange' => 'ffa500',
        'orangered' => 'ff4500',
        'orchid' => 'da70d6',
        'palegoldenrod' => 'eee8aa',
        'palegreen' => '98fb98',
        'paleturquoise' => 'afeeee',
        'palevioletred' => 'db7093',
        'papayawhip' => 'ffefd5',
        'peachpuff' => 'ffdab9',
        'peru' => 'cd853f',
        'pink' => 'ffc0cb',
        'plum' => 'dda0dd',
        'powderblue' => 'b0e0e6',
        'purple' => '800080',
        'rebeccapurple' => '663399',
        'red' => 'ff0000',
        'rosybrown' => 'bc8f8f',
        'royalblue' => '4169e1',
        'saddlebrown' => '8b4513',
        'salmon' => 'fa8072',
        'sandybrown' => 'f4a460',
        'seagreen' => '2e8b57',
        'seashell' => 'fff5ee',
        'sienna' => 'a0522d',
        'silver' => 'c0c0c0',
        'skyblue' => '87ceeb',
        'slateblue' => '6a5acd',
        'slategray' => '708090',
        'slategrey' => '708090',
        'snow' => 'fffafa',
        'springgreen' => '00ff7f',
        'steelblue' => '4682b4',
        'tan' => 'd2b48c',
        'teal' => '008080',
        'thistle' => 'd8bfd8',
        'tomato' => 'ff6347',
        'turquoise' => '40e0d0',
        'violet' => 'ee82ee',
        'wheat' => 'f5deb3',
        'white' => 'ffffff',
        'whitesmoke' => 'f5f5f5',
        'yellow' => 'ffff00',
        'yellowgreen' => '9acd32',
    ];
}
