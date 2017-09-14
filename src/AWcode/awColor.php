<?php
/**
 * Author: Mark Walker <https://awcode.com>
 * License: MIT.
 */

namespace AWcode;

use Exception;

class awColor
{
    private $_hex;
    private $_hsl;
    private $_rgb;
    private $_chroma;

    public function __construct($HexRH, $BS = false, $GL = false, $hsl = false)
    {
        if (!$BS) {
            $this->setHex($HexRH);
        } elseif (!$hsl) {
            $this->setRgb($HexRH, $BS, $GL);
        } else {
            $this->setHsl($HexRH, $BS, $GL);
        }
    }

    public static function formatHex($hex)
    {
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) == 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (ctype_xdigit($hex) && strlen($hex) == 6) {
            return $hex;
        }

        return '000000';
    }

    public static function rgbToHex($rgb = [])
    {
        if (is_array($rgb) && count($rgb) == 3) {
            $hex = str_pad(dechex($rgb[0]), 2, '0', STR_PAD_LEFT);
            $hex .= str_pad(dechex($rgb[1]), 2, '0', STR_PAD_LEFT);
            $hex .= str_pad(dechex($rgb[2]), 2, '0', STR_PAD_LEFT);

            return $hex;
        }
        throw new Exception('Missing RGB Value');
    }

    public static function hexToRgb($hex)
    {
        $hex = self::formatHex($hex);

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return [$r, $g, $b];
    }

    public static function rgbToHsl($rgb = [])
    {
        list($r, $g, $b) = $rgb;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $chroma = $max - $min;

        $luminosity = ($max + $min) / 2;

        if ($chroma == 0) {
            // Grey
            $hue = 0;
            $saturation = 0;
        } else {
            $saturation = $chroma / (1 - abs(2 * $luminosity - 1));
            switch ($max) {
                case $r:
                    $hue = 60 * fmod((($g - $b) / $chroma), 6);
                        if ($b > $g) {
                            $hue += 360;
                        }
                    break;
                case $g:
                    $hue = 60 * (($b - $r) / $chroma + 2);
                    break;
                case $b:
                    $hue = 60 * (($r - $g) / $chroma + 4);
                    break;
            }
        }
        $hue = round($hue);
        $saturation = round($saturation, 2);
        $luminosity = round($luminosity, 2);

        return array($hue, $saturation, $luminosity);
    }

    public static function hslToRgb($hsl = [])
    {
        //if(!count($hsl)){$hsl = $this->_hsl;}

        $rgb = [];
        list($hue, $saturation, $luminosity) = $hsl;
        $hue = $hue / 360;
        // If saturation is 0, the given color is grey and only
        // lightness is relevant.
        if ($saturation == 0) {
            $rgb = array($luminosity, $luminosity, $luminosity);
        }
        // Else calculate r, g, b according to hue.
        // Check http://en.wikipedia.org/wiki/HSL_and_HSV#From_HSL for details
        else {
            $chroma = (1 - abs(2 * $luminosity - 1)) * $saturation;
            $h_ = $hue * 6;
            $x = $chroma * (1 - abs((fmod($h_, 2)) - 1)); // Note: fmod because % (modulo) returns int value!!
            $m = $luminosity - round($chroma / 2, 10); // Bugfix for strange float behaviour (e.g. $l=0.17 and $s=1)

            if ($h_ >= 0 && $h_ < 1) {
                $rgb = array(($chroma + $m), ($x + $m), $m);
            } elseif ($h_ >= 1 && $h_ < 2) {
                $rgb = array(($x + $m), ($chroma + $m), $m);
            } elseif ($h_ >= 2 && $h_ < 3) {
                $rgb = array($m, ($chroma + $m), ($x + $m));
            } elseif ($h_ >= 3 && $h_ < 4) {
                $rgb = array($m, ($x + $m), ($chroma + $m));
            } elseif ($h_ >= 4 && $h_ < 5) {
                $rgb = array(($x + $m), $m, ($chroma + $m));
            } elseif ($h_ >= 5 && $h_ < 6) {
                $rgb = array(($chroma + $m), $m, ($x + $m));
            }
        }

        return $rgb; //[$r, $g, $b];
    }

    public static function hexToHsl($hex)
    {
        $rgb = self::hexToRgb($hex);

        return self::rgbToHsl($rgb);
    }

    public static function hslToHex($hsl = [])
    {
        $rgb = self::hslToRgb($hsl);

        return self::rgbToHex($rgb);
    }

    public function isLight($color = false, $contrastLimit = 130)
    {
        if ($color) {
            $rgb = $color;
        } else {
            $rgb = $this->_rgb;
        }

        $contrast = (
            $rgb[0] * $rgb[0] * .299 +
            $rgb[1] * $rgb[1] * .587 +
            $rgb[2] * $rgb[2] * .114
        );

        return $contrast > pow($contrastLimit, 2);
    }

    public function isDark($color = false,  $contrastLimit = 130)
    {
        return !$this->isLight($color, $contrastLimit);
    }

    public function isGrey($color = false)
    {
        if ($color) {
            $rgb = $color;
        } else {
            $rgb = $this->_rgb;
        }

        $max = max($rgb);
        $min = min($rgb);
        $chroma = $max - $min;

        return $chroma == 0;
    }

    public function complementary($hueShift = 180)
    {
        list($hue, $saturation, $luminosity) = $this->_hsl;

        $new_hue = ($this->_hsl[0] > $hueShift) ? ($this->_hsl[0] - $hueShift) : ($this->_hsl[0] + $hueShift);

        return self::hslToRgb([$new_hue, $saturation, $luminosity]);
    }

    public function getHex()
    {
        return $this->_hex;
    }

    public function getRgb()
    {
        return $this->_rgb;
    }
    public function getR()
    {
        return $this->_rgb[0];
    }
    public function getG()
    {
        return $this->_rgb[1];
    }
    public function getB()
    {
        return $this->_rgb[2];
    }

    public function getHsl()
    {
        return $this->_hsl;
    }
    public function getH()
    {
        return $this->_hsl[0];
    }
    public function getS()
    {
        return $this->_hsl[1];
    }
    public function getL()
    {
        return $this->_hsl[2];
    }

    public function setRgb($r, $g, $b)
    {
        $this->_rgb = [$r, $g, $b];
        $this->_hex = self::rgbToHex($this->_rgb);
        $this->_hsl = self::rgbToHsl($this->_rgb);
    }

    public function setHex($hex)
    {
        $this->_hex = self::formatHex($hex);
        $this->_rgb = self::hexToRgb($this->_hex);
        $this->_hsl = self::rgbToHsl($this->_rgb);
    }

    public function setHsl($h, $s, $l)
    {
        $this->_hsl = [$h, $s, $l];
        $this->_rbg = self::hslToRgb($this->_hsl);
        $this->_hex = self::rgbToHex($this->_rbg);
    }

    public function setR($r)
    {
        $this->setRgb($r, $this->_rgb[1], $this->_rgb[2]);
    }
    public function setG($g)
    {
        $this->setRgb($this->_rgb[0], $g, $this->_rgb[2]);
    }
    public function setB($b)
    {
        $this->setRgb($this->_rgb[0], $this->_rgb[1], $b);
    }
}
