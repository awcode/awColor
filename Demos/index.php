<?php

declare(strict_types=1);

use AWcode\awColor;

require __DIR__ . '/../vendor/autoload.php';

$base = awColor::fromName('rebeccapurple');

function swatch(awColor $color, ?string $label = null): string
{
    $bg = $color->getHexString();
    $fg = $color->pickReadable(new awColor('#000'), new awColor('#fff'))->getHexString();
    $label ??= $bg;
    return sprintf(
        '<div style="background:%s;color:%s;padding:8px 12px;border-radius:6px;font-family:monospace;display:inline-block;min-width:120px;text-align:center;margin:2px;">%s</div>',
        $bg,
        $fg,
        htmlspecialchars($label),
    );
}

function row(string $title, array $colors): string
{
    $cells = array_map(static fn (awColor $c) => swatch($c), $colors);
    return '<h3>' . htmlspecialchars($title) . '</h3><div>' . implode('', $cells) . '</div>';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>awColor Demo</title>
    <style>
        body { font-family: system-ui, sans-serif; padding: 24px; max-width: 960px; margin: auto; }
        section { margin-bottom: 32px; }
        table { border-collapse: collapse; }
        th, td { padding: 4px 8px; border: 1px solid #ddd; font-family: monospace; }
    </style>
</head>
<body>
<h1>awColor demo</h1>
<p>Base color: <?= swatch($base, $base->getHexString() . ' (rebeccapurple)') ?></p>

<section>
    <h2>Conversions</h2>
    <table>
        <tr><th>Hex</th><td><?= htmlspecialchars($base->getHexString()) ?></td></tr>
        <tr><th>RGB</th><td><?= htmlspecialchars($base->getRgbString()) ?></td></tr>
        <tr><th>HSL</th><td><?= htmlspecialchars($base->getHslString()) ?></td></tr>
        <tr><th>HSV</th><td><?= htmlspecialchars(implode(', ', array_map('strval', $base->getHsv()))) ?></td></tr>
        <tr><th>CMYK</th><td><?= htmlspecialchars(implode(', ', array_map('strval', $base->getCmyk()))) ?></td></tr>
    </table>
</section>

<section>
    <h2>Manipulation</h2>
    <?= row('Lighten', [$base->darken(0.3), $base->darken(0.15), $base, $base->lighten(0.15), $base->lighten(0.3)]) ?>
    <?= row('Saturate', [$base->desaturate(0.4), $base->desaturate(0.2), $base, $base->saturate(0.2), $base->saturate(0.4)]) ?>
    <?= row('Hue rotate', array_map(fn ($d) => $base->rotate($d), [0, 60, 120, 180, 240, 300])) ?>
    <?= row('Mix toward white/black', [$base->shade(0.4), $base->shade(0.2), $base, $base->tint(0.2), $base->tint(0.4)]) ?>
    <?= row('Invert / grayscale', [$base, $base->invert(), $base->grayscale()]) ?>
</section>

<section>
    <h2>Schemes</h2>
    <?= row('Complement', [$base, $base->complement()]) ?>
    <?= row('Triadic', $base->triadic()) ?>
    <?= row('Tetradic', $base->tetradic()) ?>
    <?= row('Split complementary', $base->splitComplementary()) ?>
    <?= row('Analogous', $base->analogous(5, 20)) ?>
    <?= row('Monochromatic', $base->monochromatic(7)) ?>
</section>

<section>
    <h2>Accessibility</h2>
    <?php $bg = new awColor('#ffffff'); ?>
    <p>Contrast vs white: <strong><?= number_format($base->contrastRatio($bg), 2) ?>:1</strong>
        — AA: <?= $base->isAccessible($bg) ? 'pass' : 'fail' ?>,
        AAA: <?= $base->isAccessible($bg, 'AAA') ? 'pass' : 'fail' ?></p>
</section>
</body>
</html>
