<?php

declare(strict_types=1);

namespace AWcode\Tests;

use AWcode\awColor;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class awColorTest extends TestCase
{
    // ------------------------------------------------------------
    // Backwards-compatible behaviour
    // ------------------------------------------------------------

    public function testConstructFromHex(): void
    {
        $color = new awColor('#ff0000');
        $this->assertSame('ff0000', $color->getHex());
        $this->assertSame([255, 0, 0], $color->getRgb());
    }

    public function testConstructFromShortHex(): void
    {
        $color = new awColor('#0f0');
        $this->assertSame('00ff00', $color->getHex());
    }

    public function testConstructFromRgb(): void
    {
        $color = new awColor(0, 0, 255);
        $this->assertSame('0000ff', $color->getHex());
        $this->assertSame([0, 0, 255], $color->getRgb());
    }

    public function testConstructFromHslLegacyFlag(): void
    {
        $color = new awColor(120, 1.0, 0.5, true);
        $this->assertSame('00ff00', $color->getHex());
    }

    public function testFormatHexHandlesInvalidInput(): void
    {
        $this->assertSame('000000', awColor::formatHex('not-a-color'));
        $this->assertSame('ffaabb', awColor::formatHex('#fab'));
        $this->assertSame('aabbcc', awColor::formatHex('AABBCC'));
    }

    public function testRgbToHex(): void
    {
        $this->assertSame('ffffff', awColor::rgbToHex([255, 255, 255]));
        $this->assertSame('000000', awColor::rgbToHex([0, 0, 0]));
        $this->assertSame('336699', awColor::rgbToHex([51, 102, 153]));
    }

    public function testRgbToHexClampsOverflow(): void
    {
        $this->assertSame('ff00ff', awColor::rgbToHex([300, -10, 999]));
    }

    public function testRgbToHexRejectsBadInput(): void
    {
        $this->expectException(InvalidArgumentException::class);
        awColor::rgbToHex([1, 2]);
    }

    public function testHexToRgb(): void
    {
        $this->assertSame([255, 0, 0], awColor::hexToRgb('ff0000'));
        $this->assertSame([255, 255, 255], awColor::hexToRgb('#fff'));
    }

    public function testHslRoundTrip(): void
    {
        foreach ([[255, 0, 0], [0, 128, 64], [12, 200, 240], [120, 120, 120]] as $rgb) {
            $hsl = awColor::rgbToHsl($rgb);
            $back = awColor::hslToRgb($hsl);
            foreach ([0, 1, 2] as $i) {
                $this->assertEqualsWithDelta($rgb[$i], $back[$i], 2, "Channel $i mismatch");
            }
        }
    }

    public function testHslToRgbReturnsBytesNotFloats(): void
    {
        $rgb = awColor::hslToRgb([0, 1.0, 0.5]);
        $this->assertSame([255, 0, 0], $rgb);
    }

    public function testSetHslBugfixUpdatesHex(): void
    {
        $color = new awColor('#000000');
        $color->setHsl(240, 1.0, 0.5);
        $this->assertSame('0000ff', $color->getHex());
    }

    public function testIsLightAndIsDark(): void
    {
        $white = new awColor('#ffffff');
        $black = new awColor('#000000');
        $this->assertTrue($white->isLight());
        $this->assertFalse($white->isDark());
        $this->assertTrue($black->isDark());
    }

    public function testIsGreyAndAlias(): void
    {
        $grey = new awColor('#808080');
        $this->assertTrue($grey->isGrey());
        $this->assertTrue($grey->isGray());
        $this->assertFalse((new awColor('#abcdef'))->isGrey());
    }

    public function testComplementaryReturnsRgbBytes(): void
    {
        $color = new awColor('#ff0000');
        $comp = $color->complementary();
        $this->assertCount(3, $comp);
        $this->assertSame([0, 255, 255], $comp);
    }

    public function testSetters(): void
    {
        $color = new awColor('#000000');
        $color->setR(255);
        $color->setG(128);
        $color->setB(64);
        $this->assertSame([255, 128, 64], $color->getRgb());
        $this->assertSame('ff8040', $color->getHex());
    }

    // ------------------------------------------------------------
    // New conversions
    // ------------------------------------------------------------

    public function testRgbHsvRoundTrip(): void
    {
        $hsv = awColor::rgbToHsv([255, 0, 0]);
        $this->assertSame([0, 1.0, 1.0], $hsv);
        $this->assertSame([255, 0, 0], awColor::hsvToRgb($hsv));
    }

    public function testRgbCmykRoundTrip(): void
    {
        $cmyk = awColor::rgbToCmyk([255, 0, 0]);
        $this->assertEqualsWithDelta([0.0, 1.0, 1.0, 0.0], $cmyk, 1e-4);
        $this->assertSame([255, 0, 0], awColor::cmykToRgb($cmyk));
    }

    public function testCmykPureBlack(): void
    {
        $cmyk = awColor::rgbToCmyk([0, 0, 0]);
        $this->assertSame([0.0, 0.0, 0.0, 1.0], $cmyk);
        $this->assertSame([0, 0, 0], awColor::cmykToRgb($cmyk));
    }

    // ------------------------------------------------------------
    // Factory helpers
    // ------------------------------------------------------------

    public function testFromName(): void
    {
        $this->assertSame('ff0000', awColor::fromName('red')->getHex());
        $this->assertSame('663399', awColor::fromName('rebeccapurple')->getHex());
    }

    public function testFromNameUnknown(): void
    {
        $this->expectException(InvalidArgumentException::class);
        awColor::fromName('flerp');
    }

    public function testFromStringHandlesAllFormats(): void
    {
        $this->assertSame('ff0000', awColor::fromString('red')->getHex());
        $this->assertSame('ff0000', awColor::fromString('#f00')->getHex());
        $this->assertSame('ff0000', awColor::fromString('#ff0000')->getHex());
        $this->assertSame('ff0000', awColor::fromString('rgb(255, 0, 0)')->getHex());
        $this->assertSame('ff0000', awColor::fromString('rgba(255, 0, 0, 0.5)')->getHex());

        $hsl = awColor::fromString('hsl(120, 100%, 50%)');
        $this->assertSame('00ff00', $hsl->getHex());
    }

    public function testFromStringTransparent(): void
    {
        $color = awColor::fromString('transparent');
        $this->assertSame(0.0, $color->getAlpha());
    }

    public function testHex8ParsedWithAlpha(): void
    {
        $color = awColor::fromString('#ff000080');
        $this->assertSame('ff0000', $color->getHex());
        $this->assertEqualsWithDelta(0.502, $color->getAlpha(), 0.005);
    }

    public function testRandomProducesValidColor(): void
    {
        $color = awColor::random();
        $this->assertSame(6, strlen($color->getHex()));
        foreach ($color->getRgb() as $channel) {
            $this->assertGreaterThanOrEqual(0, $channel);
            $this->assertLessThanOrEqual(255, $channel);
        }
    }

    // ------------------------------------------------------------
    // Manipulation
    // ------------------------------------------------------------

    public function testInvert(): void
    {
        $this->assertSame('00ffff', (new awColor('#ff0000'))->invert()->getHex());
    }

    public function testGrayscalePreservesPerceivedBrightness(): void
    {
        $gray = (new awColor('#ff0000'))->grayscale();
        $this->assertTrue($gray->isGrey());
    }

    public function testLightenAndDarken(): void
    {
        $base = awColor::fromHsl(0, 1.0, 0.5);
        $this->assertEqualsWithDelta(0.7, $base->lighten(0.2)->getL(), 0.01);
        $this->assertEqualsWithDelta(0.3, $base->darken(0.2)->getL(), 0.01);
    }

    public function testMixAverages(): void
    {
        $a = new awColor('#000000');
        $b = new awColor('#ffffff');
        $this->assertSame([128, 128, 128], $a->mix($b, 0.5)->getRgb());
    }

    public function testRotateChangesHue(): void
    {
        $rotated = (new awColor('#ff0000'))->rotate(120);
        $this->assertSame('00ff00', $rotated->getHex());
    }

    // ------------------------------------------------------------
    // Schemes
    // ------------------------------------------------------------

    public function testTriadic(): void
    {
        [$a, $b, $c] = (new awColor('#ff0000'))->triadic();
        $this->assertSame('ff0000', $a->getHex());
        $this->assertSame('00ff00', $b->getHex());
        $this->assertSame('0000ff', $c->getHex());
    }

    public function testAnalogousReturnsRequestedCount(): void
    {
        $palette = (new awColor('#ff0000'))->analogous(5, 20);
        $this->assertCount(5, $palette);
    }

    public function testMonochromaticSpansLuminance(): void
    {
        $palette = (new awColor('#ff0000'))->monochromatic(5);
        $this->assertCount(5, $palette);
        $this->assertSame(0.0, $palette[0]->getL());
        $this->assertSame(1.0, $palette[4]->getL());
    }

    // ------------------------------------------------------------
    // Accessibility
    // ------------------------------------------------------------

    public function testLuminanceExtremes(): void
    {
        $this->assertSame(0.0, (new awColor('#000000'))->luminance());
        $this->assertEqualsWithDelta(1.0, (new awColor('#ffffff'))->luminance(), 1e-9);
    }

    public function testContrastRatio(): void
    {
        $white = new awColor('#ffffff');
        $black = new awColor('#000000');
        $this->assertEqualsWithDelta(21.0, $white->contrastRatio($black), 1e-9);
    }

    public function testIsAccessible(): void
    {
        $bg = new awColor('#ffffff');
        $this->assertTrue((new awColor('#000000'))->isAccessible($bg));
        $this->assertTrue((new awColor('#000000'))->isAccessible($bg, 'AAA'));
        $this->assertFalse((new awColor('#cccccc'))->isAccessible($bg));
    }

    public function testPickReadable(): void
    {
        $bg = new awColor('#222222');
        $pick = $bg->pickReadable(new awColor('#000000'), new awColor('#ffffff'));
        $this->assertSame('ffffff', $pick->getHex());
    }

    // ------------------------------------------------------------
    // Comparison & serialization
    // ------------------------------------------------------------

    public function testEqualsAndDistance(): void
    {
        $a = new awColor('#ff0000');
        $b = new awColor('#ff0000');
        $c = new awColor('#000000');
        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
        $this->assertEqualsWithDelta(255.0, $a->distance($c), 1e-9);
    }

    public function testToArrayAndJson(): void
    {
        $color = new awColor('#ff0000');
        $array = $color->toArray();
        $this->assertSame('ff0000', $array['hex']);
        $this->assertSame([255, 0, 0], $array['rgb']);
        $this->assertJson($color->toJson());
    }

    public function testToString(): void
    {
        $this->assertSame('#ff0000', (string) (new awColor('#ff0000')));
    }

    public function testCssStrings(): void
    {
        $color = awColor::fromRgb(255, 0, 0, 0.5);
        $this->assertSame('rgb(255, 0, 0)', $color->getRgbString());
        $this->assertSame('rgba(255, 0, 0, 0.5)', $color->getRgbaString());
        $this->assertSame('hsl(0, 100%, 50%)', $color->getHslString());
        $this->assertSame('hsla(0, 100%, 50%, 0.5)', $color->getHslaString());
    }
}
