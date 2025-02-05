<?php

use Imagine\Image\ImageInterface;

class Service_Utils_Image
{
    private $image;

    public function __construct(ImageInterface $image)
    {
        $this->image = $image;
    }

    public function calculateHeightFromWidth(int $desiredWidth): int
    {
        $initialWidth = $this->image->getSize()->getWidth();
        $initialHeight = $this->image->getSize()->getHeight();

        return (int) ($initialHeight / $initialWidth) * $desiredWidth;
    }
}