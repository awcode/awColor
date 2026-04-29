# awColor

A modern, dependency-free PHP color management library: convert, manipulate, build palettes and check accessibility across hex, rgb(a), hsl(a), hsv, cmyk and CSS named colors.

[![CI](https://github.com/awcode/awColor/actions/workflows/ci.yml/badge.svg)](https://github.com/awcode/awColor/actions/workflows/ci.yml)
[![Latest Stable Version](https://poser.pugx.org/awcode/awColor/version.png)](https://packagist.org/packages/awcode/awColor)
[![Total Downloads](https://poser.pugx.org/awcode/awColor/d/total.png)](https://packagist.org/packages/awcode/awColor)
[![License](https://poser.pugx.org/awcode/awColor/license)](https://packagist.org/packages/awcode/awColor)

## Requirements

- PHP **8.2**, **8.3** or **8.4**
- No runtime dependencies

## Installation

```bash
composer require awcode/awcolor
```

## Quick start

```php
use AWcode\awColor;

// Construct from any common format
$color = new awColor('#ff0000');                  // hex (3, 4, 6 or 8 digits)
$color = awColor::fromString('rgb(255, 0, 0)');   // CSS rgb()/rgba()/hsl()/hsla()
$color = awColor::fromName('rebeccapurple');      // CSS3/CSS4 named color
$color = awColor::fromHsl(120, 1.0, 0.5);         // HSL factory
$color = awColor::fromHsv(0, 1.0, 1.0);           // HSV
$color = awColor::fromCmyk(0, 1, 1, 0);           // CMYK
$color = awColor::random();                       // Random opaque color

echo $color;                            // "#ff0000" (Stringable)
echo $color->getRgbaString();           // "rgba(255, 0, 0, 1)"
echo $color->getHslString();            // "hsl(0, 100%, 50%)"
print_r($color->getHsv());              // [0, 1.0, 1.0]
print_r($color->getCmyk());             // [0.0, 1.0, 1.0, 0.0]
```

## Manipulation (immutable, chainable)

```php
$color->lighten(0.1);          // +10% lightness
$color->darken(0.1);
$color->saturate(0.2);
$color->desaturate(0.2);
$color->rotate(60);            // hue rotation in degrees
$color->invert();
$color->grayscale();
$color->mix($other, 0.5);      // blend two colors
$color->tint(0.2);             // mix toward white
$color->shade(0.2);            // mix toward black
$color->fadeIn(0.1);
$color->fadeOut(0.1);
```

## Palettes

```php
$color->complement();          // single complementary awColor
$color->triadic();             // [base, +120°, +240°]
$color->tetradic();            // 4 colors at 90° spacing
$color->splitComplementary();  // [base, +150°, +210°]
$color->analogous(5, 20);      // 5 evenly spaced neighbors
$color->monochromatic(7);      // 7 luminance steps
```

## WCAG accessibility

```php
$fg = new awColor('#222');
$bg = new awColor('#fff');

$fg->luminance();              // WCAG 2.x relative luminance (0-1)
$fg->contrastRatio($bg);       // 21.0 for max contrast
$fg->isAccessible($bg);        // AA, normal text  (>= 4.5)
$fg->isAccessible($bg, 'AAA'); // AAA, normal text (>= 7.0)
$fg->isAccessible($bg, 'AA', 'large'); // AA, large text (>= 3.0)

// Pick the best foreground for a given background
$bg->pickReadable(new awColor('#000'), new awColor('#fff'));
```

## Comparison & serialization

```php
$a->equals($b);
$a->distance($b);              // Euclidean distance in RGB space
$color->toArray();             // ['hex' => ..., 'rgb' => ..., 'hsl' => ..., 'alpha' => ...]
$color->toJson();
```

## Backwards compatibility

The original public API is preserved:

- `new awColor($hex)` / `new awColor($r, $g, $b)` / `new awColor($h, $s, $l, true)`
- Static helpers `formatHex`, `rgbToHex`, `hexToRgb`, `rgbToHsl`, `hslToRgb`, `hexToHsl`, `hslToHex`
- Instance helpers `getHex/getRgb/getR/G/B/getHsl/getH/S/L`, `setHex/setRgb/setHsl/setR/G/B`, `isLight/isDark/isGrey`, `complementary()`

Two long-standing bugs were fixed in the process:

- `hslToRgb()` now returns 0-255 integers instead of 0-1 floats (which caused `rgbToHex()` to always return black for HSL-derived values).
- `setHsl()` previously wrote to a typo'd `_rbg` property — it now correctly updates `_rgb` and `_hex`.

## Testing

```bash
composer install
composer test
```
