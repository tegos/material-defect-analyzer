<?php

namespace App\Image;

abstract class AbstractImage extends ImageHelper
{
	protected $realWidth;
	protected $realHeight;
	protected $image;
	const MAX_INTENSITY = 255;

	public function __construct($image_path = '')
	{
		if (!empty($image_path)) {
			$image = imagecreatefromstring(file_get_contents($image_path));
			$realWidth = imagesx($image);
			$realHeight = imagesy($image);
			$this->realWidth = $realWidth;
			$this->realHeight = $realHeight;

			// create destination image
			$this->image = $image;

			// set image default background
			//$white = imagecolorallocate($this->image, 255, 255, 255);
			//imagefill($this->image, 0, 0, $white);

			$background = imagecolorallocatealpha($image, 255, 255, 255, 0);
			//imagefill($image, 0, 0, $background);
		}
	}

	public function __destruct()
	{
		if ($this->isImage($this->image)) {
			imagedestroy($this->image);
		}
	}

	public function getImage()
	{
		return $this->image;
	}

	public function setImage($image)
	{
		if ($image instanceof \GdImage) {
			$this->image = $image;
			$this->updateDimension();
		}
	}

	public function setImageByPath($image_path)
	{
		$image = imagecreatefromstring(file_get_contents($image_path));
		if ($image instanceof \GdImage) {
			$this->image = $image;
			$this->updateDimension();
		}
	}

	public function updateDimension()
	{
		$image = $this->image;
		$realWidth = imagesx($image);
		$realHeight = imagesy($image);
		$this->realWidth = $realWidth;
		$this->realHeight = $realHeight;
	}

	public function displayImage($image = null)
	{
		header("Content-type: image/png");
		if ($image) {
			imagepng($image);
		} else {
			imagepng($this->getImage());
		}

	}

	public function saveImageToFile($path, $image = null, $quality = 9)
	{
		if (!$image) {
			$image = $this->getImage();
		}
		imagepng($image, $path, $quality);
	}

	public function isImage($image): bool
	{
		return $image instanceof \GdImage;
	}

}