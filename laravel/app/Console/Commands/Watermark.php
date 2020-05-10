<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Watermark extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'watermark:make';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Overlay image watermark';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $rdi = new RecursiveDirectoryIterator(storage_path('app/public/images'));

        $rdir = new RecursiveIteratorIterator($rdi, true);

        foreach ($rdir as $file) {

            if (!is_file($file)) continue;//check exist file

            $dominant = $this->dominantColor($file->getPathName(), 5);

            $this->setWatermark($file->getPathName(), $dominant);

        }
    }


    /**
     * Find dominant color on image (exclude white and black)
     *
     * @param $image
     * @param $num
     * @param int $level
     * @return bool|false|int|string|void
     */
    private function dominantColor($image, $num, $level = 5)
    {

        $level = (int)$level;
        $palette = [];
        $details = [];# store the count of non black or white colours here ( see $exclusions )

        list($width, $height, $type, $attr) = getimagesize($image);

        if (!$type) return false;

        $img = $this->createFrom($image);

        if (!$img) return false;

        /* Colours to not factor into dominance statistics */
        $exclusions = ['000000', 'FFFFFF'];

        for ($i = 0; $i < $width; $i += $level) {
            for ($j = 0; $j < $height; $j += $level) {
                $colour = imagecolorat($img, $i, $j);
                $rgb = imagecolorsforindex($img, $colour);
                $key = sprintf('%02X%02X%02X', (round(round(($rgb['red'] / 0x33)) * 0x33)), round(round(($rgb['green'] / 0x33)) * 0x33), round(round(($rgb['blue'] / 0x33)) * 0x33));
                $palette[$key] = isset($palette[$key]) ? ++$palette[$key] : 1;

                if (!in_array($key, $exclusions)) {
                    /* add count of any non excluded colours */
                    $details[$key] = isset($details[$key]) ? ++$details[$key] : 1;
                }
            }
        }

        return array_search(max($details), $details);

    }

    /**
     * Set watermark to image and choose color for it
     *
     * @param $image
     * @param $color
     */
    private function setWatermark($image, $color)
    {
        switch ($color) {
            case '3366CC':
                $r = $g = 255;
                $b = 0;
                $name = 'yellow';
                break;
            case 'CC0033':
                $r = $g = $b = 0;
                $name = 'black';
                break;
            case '00CC33':
                $r = 250;
                $g = $b = 0;
                $name = 'red';
                break;
            default:
                $name = 'white';
                $r = $g = $b = 255;
        }

        $im = $this->createFrom($image);

        $font = storage_path('app/public/ArialRegular.ttf');

        $size = 48;

        # calculate maximum height of a character
        $bbox = imagettfbbox($size, 0, $font, 'ky');

        $x = 200;
        $y = 250 - $bbox[5];

        $text = 'test';

        $black = imagecolorallocate($im, $r, $g, $b);

        imagettftext($im, $size, 0, $x + 1, $y + 1, $black, $font, $text);

        imagejpeg($im, storage_path('app/public/image-watermark/' . $name . '.jpg'), 90);


        //$targetFilePath = storage_path('app/public/');

//        $watermarkImagePath = storage_path('app/public/watermark/photo_2019-10-17_13-06-22.jpg');
//
//        $watermarkImg = imagecreatefromjpeg($watermarkImagePath);
//
//        $transparent = imagecolorallocatealpha($watermarkImg, 0, 0, 0, 127);
//        imagefill($watermarkImg, 0, 0, $transparent);
//
//        list($width, $height, $type, $attr) = getimagesize($image);
//        if (!$type) return FALSE;
//
//        switch (image_type_to_mime_type($type)) {
//            case 'image/jpeg':
//                $im = imagecreatefromjpeg($image);
//                break;
//            case 'image/png':
//                $im = imagecreatefrompng($image);
//                break;
//            case 'image/gif':
//                $im = imagecreatefromgif($image);
//                break;
//            default:
//                return FALSE;
//        }
//
//        // Set the margins for the watermark
//        $marge_right = 50;
//        $marge_bottom = 50;
//
//        // Get the height/width of the watermark image
//        $sx = imagesx($watermarkImg);
//        $sy = imagesy($watermarkImg);
//
//        // Copy the watermark image onto our photo using the margin offsets and
//        // the photo width to calculate the positioning of the watermark.
//        imagecopy($im, $watermarkImg, imagesx($im) - $sx - $marge_right, imagesy($im) - $sy - $marge_bottom, 0, 0, imagesx($watermarkImg), imagesy($watermarkImg));
//
//        // Save image and free memory
//        imagepng($im, storage_path('app/public/test.jpg'));
//        imagedestroy($im);
    }

    /**
     * @param $image
     * @return bool|false|resource|void
     */
    private function createFrom($image)
    {
        $type = getimagesize($image)[2];

        if (!$type) return false;

        switch (image_type_to_mime_type($type)) {
            case 'image/jpeg':
                $im = imagecreatefromjpeg($image);
                break;
            case 'image/png':
                $im = imagecreatefrompng($image);
                break;
            case 'image/gif':
                $im = imagecreatefromgif($image);
                break;
            default:
                return false;
        }

        return $im;
    }
}
