<!DOCTYPE>
<html>
<head>
	<title>awColor Demos</title>
</head>
<body>
<?php
include("../src/AWcode/awColor.php");
?>

<pre>
&lt;?php
$color = new \AWcode\awColor('#00ff00');
echo($color->getHex());
print_r($color->getRbg());
print_r($color->getHsl());
?&gt;
</pre>
<?php
$color = new \AWcode\awColor('#00ff00');
//echo($color->getHex().'<br>');
//print_r($color->getRgb());echo('<br>');
//print_r($color->getHsl());echo('<br>');
?>
<hr>
<table style="padding:3px;">
<tr>
	<th>Hex</th><th>RGB</th><th>HSL</th><th>isLight</th><th>isDark</th><th>isGrey</th><th>Compliment</th>
</tr>
<?php
for($r = 0; $r<=255; $r+=15){
	for($g = 0; $g<=255; $g+=15){
		for($b = 0; $b<=255; $b+=15){
			$color->setRgb($r,$g,$b);
			$comp = $color->complementary();
			?>
			<tr>
				<td style="background:#<?=$color->getHex()?>; color:<?=$color->isLight()?'#000000':'#FFFFFF'?>;">#<?=$color->getHex()?></td>
				<td><?=$color->getR()?>, <?=$color->getG()?>, <?=$color->getB()?></td>
				<td><?=$color->getH()?>, <?=$color->getS()?>, <?=$color->getL()?></td>
				<td><?=$color->isLight()?'<span style="background:green">Yes</span>':'No'?></td>
				<td><?=$color->isDark()?'<span style="background:green">Yes</span>':'No'?></td>
				<td><?=$color->isGrey()?'<span style="background:green">Yes</span>':'No'?></td>
				<td style="background:#<?=\AWcode\awColor::rgbToHex([$comp[0],$comp[1],$comp[2]])?>; color:<?=$color->isLight($comp)?'#000000':'#FFFFFF'?>;">#<?=\AWcode\awColor::rgbToHex([$comp[0],$comp[1],$comp[2]])?></td>
			</tr>
			<?php
		}
	}
}
?>
</body>
</html>
