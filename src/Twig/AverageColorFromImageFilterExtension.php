<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use GdImage;

/**
 * Description of AgoFilterExtension.
 */
class AverageColorFromImageFilterExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('average_color', $this->getAverageColorFromImage(...)),
        ];
    }

    public function getAverageColorFromImage(string $imagePath): string
    {

        $image = $this->createImageFromFile($imagePath);

        if($image === false) {
            return '#000000';
        }

        $pixIm = imagescale($image, 1,1);
        $rgb = imagecolorsforindex($pixIm, imagecolorat($pixIm, 0, 0));

        return sprintf("#%02x%02x%02x", $rgb["red"], $rgb["green"], $rgb["blue"]);
    }

    private function createImageFromFile(string $imagePath): GdImage|false {
        $info = getimagesize($imagePath);
        $mime = image_type_to_mime_type($info[2]);
        return match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($imagePath),
            'image/png' => imagecreatefrompng($imagePath),
            'image/gif' => imagecreatefromgif($imagePath),
            default => false,
        };

    }
}
