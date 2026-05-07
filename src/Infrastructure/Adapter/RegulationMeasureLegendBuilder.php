<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

final class RegulationMeasureLegendBuilder
{
    private const SWATCH_WIDTH = 80;
    private const SWATCH_HEIGHT = 8;
    private const STROKE_WIDTH = 4;

    private const STYLES = [
        'noEntry' => ['color' => [0xCE, 0x05, 0x00], 'dashed' => false],
        'speedLimitation' => ['color' => [0xF6, 0xC4, 0x3C], 'dashed' => false],
        'parkingProhibited' => ['color' => [0x00, 0x00, 0x00], 'dashed' => false],
        'alternateRoad' => ['color' => [0x6A, 0x6A, 0xF4], 'dashed' => true],
    ];

    public function buildSwatches(): array
    {
        $result = [];

        foreach (self::STYLES as $type => $style) {
            $result[$type] = $this->buildSwatch($style['color'], $style['dashed']);
        }

        return $result;
    }

    private function buildSwatch(array $rgb, bool $dashed): string
    {
        $img = imagecreatetruecolor(self::SWATCH_WIDTH, self::SWATCH_HEIGHT);
        imagealphablending($img, false);
        imagesavealpha($img, true);
        $transparent = imagecolorallocatealpha($img, 255, 255, 255, 127);
        imagefill($img, 0, 0, $transparent);
        imagealphablending($img, true);

        $stroke = imagecolorallocate($img, $rgb[0], $rgb[1], $rgb[2]);
        imagesetthickness($img, self::STROKE_WIDTH);
        $y = (int) round(self::SWATCH_HEIGHT / 2);

        if ($dashed) {
            $segLen = 12;
            $gapLen = 6;

            for ($x = 0; $x < self::SWATCH_WIDTH; $x += $segLen + $gapLen) {
                imageline($img, $x, $y, min($x + $segLen, self::SWATCH_WIDTH), $y, $stroke);
            }
        } else {
            imageline($img, 0, $y, self::SWATCH_WIDTH, $y, $stroke);
        }

        ob_start();
        imagepng($img);
        $bytes = ob_get_clean();
        imagedestroy($img);

        return 'data:image/png;base64,' . base64_encode((string) $bytes);
    }
}
