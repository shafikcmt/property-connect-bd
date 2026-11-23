<?php

namespace Database\Seeders\Demo;

/**
 * Procedural, clearly illustrative architectural artwork (skyline / tower / house)
 * used only by the demo seeder. Never a substitute for real photography.
 */
class DemoArt
{
    /** @return string path to a temporary PNG */
    public static function render(int $seed, string $style = 'skyline', int $w = 1600, int $h = 1067): string
    {
        mt_srand($seed);
        $im = imagecreatetruecolor($w, $h);
        imageantialias($im, true);

        $palettes = [
            [[11, 31, 58], [22, 80, 110], [236, 190, 120]],   // dusk navy → teal → amber
            [[20, 60, 100], [120, 180, 210], [250, 235, 210]],  // day
            [[8, 22, 44], [14, 90, 100], [200, 230, 220]],      // night teal
            [[30, 40, 70], [150, 110, 140], [255, 200, 150]],   // warm sunset
        ];
        [$top, $mid, $glow] = $palettes[$seed % count($palettes)];

        for ($y = 0; $y < $h; $y++) {
            $t = $y / $h;
            $c = $t < 0.6 ? self::mix($top, $mid, $t / 0.6) : self::mix($mid, $glow, ($t - 0.6) / 0.4);
            imageline($im, 0, $y, $w, $y, imagecolorallocate($im, ...$c));
        }

        // soft sun / moon glow
        $gx = mt_rand((int) ($w * 0.2), (int) ($w * 0.8));
        for ($r = 260; $r > 0; $r -= 6) {
            $a = (int) (127 - (127 * (1 - $r / 260) * 0.35));
            imagefilledellipse($im, $gx, (int) ($h * 0.52), $r * 2, $r * 2, imagecolorallocatealpha($im, $glow[0], $glow[1], $glow[2], $a));
        }

        match ($style) {
            'tower' => self::tower($im, $w, $h, $glow),
            'house' => self::house($im, $w, $h, $glow),
            default => self::skyline($im, $w, $h, $glow),
        };

        // ground
        $ground = imagecolorallocate($im, (int) ($top[0] * 0.5), (int) ($top[1] * 0.5), (int) ($top[2] * 0.6));
        imagefilledrectangle($im, 0, (int) ($h * 0.88), $w, $h, $ground);

        $path = tempnam(sys_get_temp_dir(), 'demo').'.png';
        imagepng($im, $path);
        imagedestroy($im);

        return $path;
    }

    private static function skyline($im, int $w, int $h, array $glow): void
    {
        $base = (int) ($h * 0.88);
        foreach ([0.35, 0.2, 0.0] as $layer => $fade) {
            $x = -40;
            while ($x < $w) {
                $bw = mt_rand(70, 170) - $layer * 20;
                $bh = mt_rand((int) ($h * 0.18), (int) ($h * (0.55 - $layer * 0.1)));
                $shade = 28 + $layer * 22;
                $col = imagecolorallocate($im, $shade, $shade + 12, $shade + 34);
                imagefilledrectangle($im, $x, $base - $bh, $x + $bw, $base, $col);
                if ($layer === 2) {
                    self::windows($im, $x, $base - $bh, $bw, $bh, $glow);
                }
                $x += $bw + mt_rand(6, 30);
            }
        }
    }

    private static function tower($im, int $w, int $h, array $glow): void
    {
        $base = (int) ($h * 0.88);
        $bw = (int) ($w * 0.26);
        $x = (int) (($w - $bw) / 2) + mt_rand(-120, 120);
        $bh = (int) ($h * 0.78);
        imagefilledrectangle($im, $x, $base - $bh, $x + $bw, $base, imagecolorallocate($im, 26, 44, 74));
        imagefilledrectangle($im, $x + $bw, $base - $bh + 20, $x + $bw + 60, $base, imagecolorallocate($im, 16, 30, 54));
        self::windows($im, $x, $base - $bh, $bw, $bh, $glow, 0.82);
        // balconies
        $line = imagecolorallocate($im, 210, 220, 230);
        for ($y = $base - $bh + 40; $y < $base; $y += 46) {
            imageline($im, $x, $y, $x + $bw, $y, $line);
        }
        // side blocks
        foreach ([-1, 1] as $s) {
            $sw = (int) ($bw * 0.55);
            $sh = (int) ($bh * 0.5);
            $sx = $s < 0 ? $x - $sw - 30 : $x + $bw + 90;
            imagefilledrectangle($im, $sx, $base - $sh, $sx + $sw, $base, imagecolorallocate($im, 34, 58, 92));
            self::windows($im, $sx, $base - $sh, $sw, $sh, $glow, 0.6);
        }
    }

    private static function house($im, int $w, int $h, array $glow): void
    {
        $base = (int) ($h * 0.88);
        $x = (int) ($w * 0.22);
        $bw = (int) ($w * 0.56);
        $bh = (int) ($h * 0.3);
        imagefilledrectangle($im, $x, $base - $bh, $x + $bw, $base, imagecolorallocate($im, 232, 226, 214));
        imagefilledrectangle($im, $x + (int) ($bw * 0.5), $base - $bh - 120, $x + $bw, $base - $bh, imagecolorallocate($im, 205, 198, 184));
        imagefilledrectangle($im, $x - 30, $base - $bh - 14, $x + $bw + 30, $base - $bh, imagecolorallocate($im, 40, 48, 60));
        self::windows($im, $x, $base - $bh, $bw, $bh, $glow, 0.9, 120);
        imagefilledrectangle($im, $x + 60, $base - 170, $x + 140, $base, imagecolorallocate($im, 70, 52, 40));
        // trees
        foreach ([0.1, 0.88] as $tx) {
            imagefilledellipse($im, (int) ($w * $tx), $base - 140, 200, 260, imagecolorallocate($im, 24, 84, 66));
            imagefilledrectangle($im, (int) ($w * $tx) - 8, $base - 40, (int) ($w * $tx) + 8, $base, imagecolorallocate($im, 60, 44, 32));
        }
    }

    private static function windows($im, int $x, int $y, int $w, int $h, array $glow, float $density = 0.7, int $size = 14): void
    {
        $lit = imagecolorallocate($im, ...array_map(fn ($c) => min(255, $c + 10), $glow));
        $dark = imagecolorallocate($im, 14, 26, 46);
        $gap = (int) ($size * 1.7);
        for ($wy = $y + 18; $wy < $y + $h - 16; $wy += $gap + 6) {
            for ($wx = $x + 12; $wx < $x + $w - $size; $wx += $gap) {
                imagefilledrectangle($im, $wx, $wy, $wx + $size - 4, $wy + (int) ($size * 1.1), mt_rand(0, 100) / 100 < $density * 0.55 ? $lit : $dark);
            }
        }
    }

    private static function mix(array $a, array $b, float $t): array
    {
        $t = max(0, min(1, $t));

        return [(int) ($a[0] + ($b[0] - $a[0]) * $t), (int) ($a[1] + ($b[1] - $a[1]) * $t), (int) ($a[2] + ($b[2] - $a[2]) * $t)];
    }
}
