<?php

namespace App\Image;

class ImageGrid extends AbstractImage
{

    private $gridWidth;
    private $gridHeight;

    public function __construct($image_path, $gridWidth, $gridHeight)
    {
        $this->gridWidth = $gridWidth > 0 ? $gridWidth : 1;
        $this->gridHeight = $gridHeight > 0 ? $gridHeight : 1;

        parent::__construct($image_path);

    }

    public function addGridToImage()
    {
        $black = imagecolorallocate($this->image, 0, 0, 0);
        imagesetthickness($this->image, 2);
        $cellWidth = ($this->realWidth - 1) / $this->gridWidth;   // note: -1 to avoid writting
        $cellHeight = ($this->realHeight - 1) / $this->gridHeight; // a pixel outside the image
        for ($x = 0; ($x <= $this->gridWidth); $x++) {
            for ($y = 0; ($y <= $this->gridHeight); $y++) {
                imageline($this->image, ($x * $cellWidth), 0, ($x * $cellWidth), $this->realHeight, $black);
                imageline($this->image, 0, ($y * $cellHeight), $this->realWidth, ($y * $cellHeight), $black);
            }
        }
    }

    public function putImage($img, $sizeW, $sizeH, $posX, $posY)
    {
        // Cell width
        $cellWidth = $this->realWidth / $this->gridWidth;
        $cellHeight = $this->realHeight / $this->gridHeight;

        // Conversion of our virtual sizes/positions to real ones
        $realSizeW = ceil($cellWidth * $sizeW);
        $realSizeH = ceil($cellHeight * $sizeH);
        $realPosX = ($cellWidth * $posX);
        $realPosY = ($cellHeight * $posY);

        $img = $this->resizePreservingAspectRatio($img, $realSizeW, $realSizeH);

        // Copying the image
        imagecopyresampled($this->image, $img, $realPosX, $realPosY, 0, 0, $realSizeW, $realSizeH, imagesx($img), imagesy($img));
    }

    public function resizePreservingAspectRatio($img, $targetWidth, $targetHeight)
    {
        $srcWidth = imagesx($img);
        $srcHeight = imagesy($img);

        $srcRatio = $srcWidth / $srcHeight;
        $targetRatio = $targetWidth / $targetHeight;
        if (($srcWidth <= $targetWidth) && ($srcHeight <= $targetHeight)) {
            $imgTargetWidth = $srcWidth;
            $imgTargetHeight = $srcHeight;
        } else if ($targetRatio > $srcRatio) {
            $imgTargetWidth = (int)($targetHeight * $srcRatio);
            $imgTargetHeight = $targetHeight;
        } else {
            $imgTargetWidth = $targetWidth;
            $imgTargetHeight = (int)($targetWidth / $srcRatio);
        }

        $targetImg = imagecreatetruecolor($targetWidth, $targetHeight);

        imagecopyresampled(
            $targetImg,
            $img,
            ($targetWidth - $imgTargetWidth) / 2, // centered
            ($targetHeight - $imgTargetHeight) / 2, // centered
            0,
            0,
            $imgTargetWidth,
            $imgTargetHeight,
            $srcWidth,
            $srcHeight
        );

        return $targetImg;
    }

    /**
     * @param int $m - row
     * @param int $n - column
     * @return bool|resource
     */
    public function getImageByPosition($m = 0, $n = 0)
    {
        // Cell width
        $cellWidth = floor($this->realWidth / $this->gridWidth);
        $cellHeight = floor($this->realHeight / $this->gridHeight);
        $x = $n * $cellWidth;
        $y = $m * $cellHeight;
        $width = $cellWidth;
        $height = $cellHeight;

        $crop = imagecrop($this->image,
            [
                'x' => $x,
                'y' => $y,
                'width' => $width,
                'height' => $height
            ]
        );

        return $crop;
    }

}