<?php
$size = (int)($argv[1] ?? 512);
$outFile = $argv[2] ?? __DIR__ . '/../images/icon-' . $size . '.png';

$img = imagecreatetruecolor($size, $size);

// Colors
$bg = imagecolorallocate($img, 26, 26, 46);      // #1a1a2e
$red = imagecolorallocate($img, 220, 53, 69);     // #dc3545
$white = imagecolorallocate($img, 255, 255, 255);
$darkRed = imagecolorallocate($img, 167, 29, 42); // #a71d2a

// Background circle
imagefilledellipse($img, $size/2, $size/2, $size*0.9, $size*0.9, $bg);

// Red circle border
$thickness = max(4, $size * 0.03);
for ($i = 0; $i < $thickness; $i++) {
    $d = $size * 0.88 - ($i * 2);
    imagearc($img, $size/2, $size/2, $d, $d, 0, 360, $red);
}

// Wrench icon (simplified)
$cx = $size / 2;
$cy = $size / 2;
$r = $size * 0.25;

// Wrench handle (diagonal line)
$handleWidth = max(6, $size * 0.06);
imagesetthickness($img, $handleWidth);
$hx1 = $cx - $r * 0.7;
$hy1 = $cy + $r * 0.7;
$hx2 = $cx + $r * 0.7;
$hy2 = $cy - $r * 0.7;
imageline($img, $hx1, $hy1, $hx2, $hy2, $white);

// Wrench head (circle at top-right)
$headR = $size * 0.1;
imagefilledellipse($img, $cx + $r * 0.5, $cy - $r * 0.5, $headR * 2, $headR * 2, $white);
imagefilledellipse($img, $cx + $r * 0.5, $cy - $r * 0.5, $headR * 1.2, $headR * 1.2, $bg);

// Wrench tail (circle at bottom-left)
$tailR = $size * 0.07;
imagefilledellipse($img, $cx - $r * 0.5, $cy + $r * 0.5, $tailR * 2, $tailR * 2, $white);

// Text "WN" below wrench
$fontSize = max(12, (int)($size * 0.12));
$font = 5; // GD built-in font
$text = "WN";
$tw = imagefontwidth($font) * strlen($text);
$th = imagefontheight($font);
imagestring($img, $font, $cx - $tw/2, $cy + $r * 0.9, $text, $white);

// Save
$dir = dirname($outFile);
if (!is_dir($dir)) mkdir($dir, 0755, true);
imagepng($img, $outFile);
imagedestroy($img);

echo "Created: $outFile ($size x $size)\n";
