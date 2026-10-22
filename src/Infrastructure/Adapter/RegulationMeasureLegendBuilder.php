<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

final class RegulationMeasureLegendBuilder
{
    private const SWATCH_WIDTH = 80;
    private const SWATCH_HEIGHT = 8;
    private const STROKE_WIDTH = 4;
    // dasharray units are multiples of the stroke width (MapLibre convention).
    private const DASH_UNIT = self::STROKE_WIDTH;

    // Styles mirror the map styles in assets/maps/measure_type_styles.js.
    // dasharray values are expressed in multiples of the stroke width; a line is
    // solid when every gap (odd-indexed value) is 0.
    private const STYLES = [
        'noEntry' => ['color' => [0xCE, 0x05, 0x00], 'dasharray' => [1, 1]],
        'speedLimitation' => ['color' => [0xF6, 0xC4, 0x3C], 'dasharray' => [1, 0]],
        'parkingProhibited' => [
            'color' => [0xFF, 0xFF, 0xFF],
            'dasharray' => [1, 0],
            'lineWidth' => 1,
            'borderColor' => [0xFA, 0x7A, 0x35],
            'borderWidth' => 4,
        ],
        'alternateRoad' => ['color' => [0x6A, 0x6A, 0xF4], 'dasharray' => [2, 2]],
        'noOvertaking' => ['color' => [0xDA, 0x70, 0xD6], 'dasharray' => [1, 2, 4, 2]],
    ];

    public function buildSwatches(): array
    {
        $result = [];

        foreach (self::STYLES as $type => $style) {
            $result[$type] = $this->buildSwatch($style);
        }

        return $result;
    }

    private function buildSwatch(array $style): string
    {
        $img = imagecreatetruecolor(self::SWATCH_WIDTH, self::SWATCH_HEIGHT);
        imagealphablending($img, false);
        imagesavealpha($img, true);
        $transparent = imagecolorallocatealpha($img, 255, 255, 255, 127);
        imagefill($img, 0, 0, $transparent);
        imagealphablending($img, true);

        $cy = (int) round(self::SWATCH_HEIGHT / 2);

        if (isset($style['borderColor'])) {
            $this->drawBorderedLine($img, $style, $cy);
        } else {
            $lineWidth = $style['lineWidth'] ?? self::STROKE_WIDTH;
            $color = imagecolorallocate($img, $style['color'][0], $style['color'][1], $style['color'][2]);
            imagesetthickness($img, $lineWidth);
            $this->drawDashedLine($img, $style['dasharray'], $cy, $color);
        }

        ob_start();
        imagepng($img);
        $bytes = ob_get_clean();
        imagedestroy($img);

        return 'data:image/png;base64,' . base64_encode((string) $bytes);
    }

    /**
     * @param \GdImage $img
     * @param int[]    $dasharray
     */
    private function drawDashedLine($img, array $dasharray, int $y, int $color): void
    {
        $gaps = array_filter($dasharray, fn ($value, $index) => $index % 2 === 1, \ARRAY_FILTER_USE_BOTH);

        // A line with no gaps is solid.
        if (array_sum($gaps) === 0) {
            imageline($img, 0, $y, self::SWATCH_WIDTH, $y, $color);

            return;
        }

        $segments = array_map(fn (int $value) => $value * self::DASH_UNIT, $dasharray);
        $count = \count($segments);
        $x = 0;
        $index = 0;

        while ($x < self::SWATCH_WIDTH) {
            $length = $segments[$index % $count];
            $isDash = $index % 2 === 0;

            if ($isDash && $length > 0) {
                imageline($img, $x, $y, (int) min($x + $length, self::SWATCH_WIDTH), $y, $color);
            }

            $x += $length;
            ++$index;
        }
    }

    /**
     * Draws a rounded line with a colored border and a different-colored center,
     * e.g. parkingProhibited: orange rounded border with a white center.
     *
     * @param \GdImage $img
     */
    private function drawBorderedLine($img, array $style, int $cy): void
    {
        $lineWidth = $style['lineWidth'] ?? self::STROKE_WIDTH;
        $borderWidth = $style['borderWidth'] ?? 0;
        $innerHalf = (int) floor($lineWidth / 2);
        $outerHalf = $innerHalf + $borderWidth;

        $border = imagecolorallocate($img, $style['borderColor'][0], $style['borderColor'][1], $style['borderColor'][2]);
        $center = imagecolorallocate($img, $style['color'][0], $style['color'][1], $style['color'][2]);

        $this->fillRoundedLine($img, 0, self::SWATCH_WIDTH, $cy, $outerHalf, $border);
        $this->fillRoundedLine($img, $outerHalf, self::SWATCH_WIDTH - $outerHalf, $cy, $innerHalf, $center);
    }

    /** @param \GdImage $img */
    private function fillRoundedLine($img, int $x0, int $x1, int $cy, int $half, int $color): void
    {
        $diameter = $half * 2;
        imagefilledrectangle($img, $x0, $cy - $half, $x1, $cy + $half, $color);
        imagefilledellipse($img, $x0, $cy, $diameter, $diameter, $color);
        imagefilledellipse($img, $x1, $cy, $diameter, $diameter, $color);
    }
}
