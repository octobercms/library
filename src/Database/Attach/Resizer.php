<?php namespace October\Rain\Database\Attach;

use Symfony\Component\HttpFoundation\File\File as FileObj;
use Exception;

/**
 * Image resizer
 *
 * Usage:
 *      Resizer::open(mixed $file)
 *          ->resize(int $width , int $height, string 'exact, portrait, landscape, auto, fit or crop')
 *          ->save(string 'path/to/file.jpg', int $quality);
 *
 *      // Resize and save an image.
 *      Resizer::open(Input::file('field_name'))
 *          ->resize(800, 600, 'crop')
 *          ->save('path/to/file.jpg', 100);
 *
 *      // Recompress an image.
 *      Resizer::open('path/to/image.jpg')
 *          ->save('path/to/new_image.jpg', 60);
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
class Resizer
{
    /**
     * @var Resource The symfony uploaded file object.
     */
    protected $file;

    /**
     * @var string The extension of the uploaded file.
     */
    protected $extension;

    /**
     * @var string The mime type of the uploaded file.
     */
    protected $mime;

    /**
     * @var Resource The image (on disk) that's being resized.
     */
    protected $image;

    /**
     * @var Resource The cached, original image.
     */
    protected $originalImage;

    /**
     * @var int Original width of the image being resized.
     */
    protected $width;

    /**
     * @var int Original height of the image being resized.
     */
    protected $height;

    /**
     * @var int|null Exif orientation of image
     */
    protected $orientation;

    /**
     * @var array Array of options used for resizing.
     */
    protected $options = [];

    /**
     * Instantiates the Resizer and receives the path to an image we're working with
     * @param mixed $file The file array provided by Laravel's Input::file('field_name') or a path to a file
     * @throws Exception
     */
    public function __construct($file)
    {
        if (!extension_loaded('gd')) {
            echo 'GD PHP library required.'.PHP_EOL;
            exit(1);
        }

        if (is_string($file)) {
            $file = new FileObj($file);
        }

        // Get the file extension
        $this->extension = $file->guessExtension();
        $this->mime = $file->getMimeType();

        // Open up the file
        $this->image = $this->originalImage = $this->openImage($file);

        // Get width and height of our image
        $this->orientation  = $this->getOrientation($file);

        // Get width and height of our image
        $this->width  = $this->getWidth();
        $this->height = $this->getHeight();

        // Set default options
        $this->setOptions([]);
    }

    /**
     * Static call, Laravel style.
     * Returns a new Resizer object, allowing for chainable calls
     * @param  mixed $file The file array provided by Laravel's Input::file('field_name') or a path to a file
     * @return Resizer
     * @throws Exception
     */
    public static function open($file)
    {
        return new Resizer($file);
    }

    /**
     * Manipulate an image resource in order to keep transparency for PNG and GIF files.
     * @param $img
     */
    protected function retainImageTransparency($img)
    {
        if ($this->mime === 'image/gif') {
            $alphaColor = array('red' => 0, 'green' => 0, 'blue' => 0);
            $alphaIndex = imagecolortransparent($this->image);
            if ($alphaIndex >= 0) {
                $alphaColor = imagecolorsforindex($this->image, $alphaIndex);
            }
            $alphaIndex = imagecolorallocatealpha($img, $alphaColor['red'], $alphaColor['green'], $alphaColor['blue'], 127);
            imagefill($img, 0, 0, $alphaIndex);
            imagecolortransparent($img, $alphaIndex);
        } else if ($this->mime === 'image/png' || $this->mime === 'image/webp') {
            imagealphablending($img, false);
            imagesavealpha($img, true);
        }
    }

    /**
     * Resets the image back to the original.
     * @return self
     */
    public function reset()
    {
        $this->image = $this->originalImage;

        return $this;
    }

    /**
     * Sets resizer options. Available options are:
     *  - mode: Either exact, portrait, landscape, auto, fit or crop.
     *  - offset: The offset of the crop = [ left, top ]
     *  - sharpen: Sharpen image, from 0 - 100 (default: 0)
     *  - interlace: Interlace image,  Boolean: false (disabled: default), true (enabled)
     *  - quality: Image quality, from 0 - 100 (default: 90)
     * @param array $options Set of resizing option
     * @return self
     */
    public function setOptions($options)
    {
        $this->options = array_merge([
            'mode'      => 'auto',
            'offset'    => [0, 0],
            'sharpen'   => 0,
            'interlace' => false,
            'quality'   => 90
        ], $options);

        return $this;
    }

    /**
     * Sets an individual resizer option.
     * @param string $option Option name to set
     * @param mixed $value Option value to set
     * @return self
     */
    protected function setOption($option, $value)
    {
        $this->options[$option] = $value;

        return $this;
    }

    /**
     * Gets an individual resizer option.
     * @param string $option Option name to get
     * @return mixed Depends on the option
     */
    protected function getOption($option)
    {
        return array_get($this->options, $option);
    }

    /**
     * Receives the image's exif orientation
     * @param \Symfony\Component\HttpFoundation\File\File $file
     * @return int|null
     */
    protected function getOrientation($file)
    {
        $filePath = $file->getPathname();

        if ($this->mime != 'image/jpeg' || !function_exists('exif_read_data')) {
            return null;
        }

        /*
         * Reading the exif data is prone to fail due to bad data
         */
        $exif = @exif_read_data($filePath);

        if (!isset($exif['Orientation'])) {
            return null;
        }

        // Only take care of spin orientations, no mirrored
        if (!in_array($exif['Orientation'], [1, 3, 6, 8], true)) {
            return null;
        }

        return $exif['Orientation'];
    }

    /**
     * Receives the image's width while respecting
     * the exif orientation
     * @return int
     */
    protected function getWidth()
    {
        switch ($this->orientation) {
            case 6:
            case 8:
                return imagesy($this->image);

            case 1:
            case 3:
            default:
                return imagesx($this->image);
        }
    }

    /**
     * Receives the image's height while respecting
     * the exif orientation
     * @return int
     */
    protected function getHeight()
    {
        switch ($this->orientation) {
            case 6:
            case 8:
                return imagesx($this->image);

            case 1:
            case 3:
            default:
                return imagesy($this->image);
        }
    }

    /**
     * Receives the original but rotated image
     * according to exif orientation
     * @return resource (gd)
     */
    protected function getRotatedOriginal()
    {
        switch ($this->orientation) {
            case 6:
                $angle = 270.0;
                break;

            case 8:
                $angle = 90.0;
                break;

            case 3:
                $angle = 180.0;
                break;

            case 1:
            default:
                return $this->image;
        }

        $bgcolor = imagecolorallocate($this->image, 0, 0, 0);
        return imagerotate($this->image, $angle, $bgcolor);
    }

    /**
     * Resizes and/or crops an image
     * @param int $newWidth The width of the image
     * @param int $newHeight The height of the image
     * @param array $options A set of resizing options
     * @return self
     * @throws Exception
     */
    public function resize($newWidth, $newHeight, $options = [])
    {
        $this->setOptions($options);

        /*
         * Sanitize input
         */
        $newWidth = (int) $newWidth;
        $newHeight = (int) $newHeight;

        if (!$newWidth && !$newHeight) {
            $newWidth = $this->width;
            $newHeight = $this->height;
        }
        elseif (!$newWidth) {
            $newWidth = $this->getSizeByFixedHeight($newHeight);
        }
        elseif (!$newHeight) {
            $newHeight = $this->getSizeByFixedWidth($newWidth);
        }

        // Get optimal width and height - based on supplied mode.
        list($optimalWidth, $optimalHeight) = $this->getDimensions($newWidth, $newHeight);

        // Resample - create image canvas of x, y size
        $imageResized = imagecreatetruecolor($optimalWidth, $optimalHeight);
        $this->retainImageTransparency($imageResized);

        // get the rotated the original image according to exif orientation
        $rotatedOriginal = $this->getRotatedOriginal();

        // Create the new image
        imagecopyresampled($imageResized, $rotatedOriginal, 0, 0, 0, 0, $optimalWidth, $optimalHeight, $this->width, $this->height);

        $this->image = $imageResized;

        /*
         * Apply sharpness
         */
        if ($sharpen = $this->getOption('sharpen')) {
            $this->sharpen($sharpen);
        }

        /*
         * If mode is crop: find center and use for the cropping.
         */
        if ($this->getOption('mode') == 'crop') {
            $offset = $this->getOption('offset');
            $cropStartX = ($optimalWidth  / 2) - ($newWidth  / 2) - $offset[0];
            $cropStartY = ($optimalHeight / 2) - ($newHeight / 2) - $offset[1];
            $this->crop($cropStartX, $cropStartY, $newWidth, $newHeight);
        }

        return $this;
    }

    /**
     * Sharpen the image across a scale of 0 - 100
     * @param int $sharpness
     * @return self
     */
    public function sharpen($sharpness)
    {
        if ($sharpness <= 0 || $sharpness > 100) {
            return $this;
        }

        $image = $this->image;

        // Normalize sharpening value
        $kernelCenter = exp((80 - (float)$sharpness) / 18) + 9;

        $matrix = array(
            [-1, -1, -1],
            [-1, $kernelCenter, -1],
            [-1, -1, -1],
        );

        $divisor = array_sum(array_map('array_sum', $matrix));

        imageconvolution($image, $matrix, $divisor, 0);

        $this->image = $image;

        return $this;
    }

    /**
     * Crops an image from its center
     * @param int $cropStartX Start on X axis
     * @param int $cropStartY Start on Y axis
     * @param int $newWidth The new width
     * @param int $newHeight The new height
     * @param int $srcWidth Source area width.
     * @param int $srcHeight Source area height.
     * @return self
     */
    public function crop($cropStartX, $cropStartY, $newWidth, $newHeight, $srcWidth = null, $srcHeight = null)
    {
        $image = $this->image;

        if ($srcWidth === null) {
            $srcWidth = $newWidth;
        }
        if ($srcHeight === null) {
            $srcHeight = $newHeight;
        }

        // Create a new canvas
        $imageResized = imagecreatetruecolor($newWidth, $newHeight);
        $this->retainImageTransparency($imageResized);

        // Crop the image to the requested size
        imagecopyresampled($imageResized, $image, 0, 0, $cropStartX, $cropStartY, $newWidth, $newHeight, $srcWidth, $srcHeight);

        $this->image = $imageResized;

        return $this;
    }

    /**
     * Save the image based on its file type.
     * @param string $savePath Where to save the image
     * @throws Exception Thrown for invalid extension
     */
    public function save($savePath)
    {
        $image = $this->image;

        $imageQuality = $this->getOption('quality');

        if ($this->getOption('interlace')) {
            imageinterlace($image, true);
        }

        // Determine the image type from the destination file
        $extension = pathinfo($savePath, PATHINFO_EXTENSION) ?: $this->extension;

        // Create and save an image based on it's extension
        switch (strtolower($extension)) {
            case 'jpg':
            case 'jpeg':
                // Check JPG support is enabled
                if (imagetypes() & IMG_JPG) {
                    imagejpeg($image, $savePath, $imageQuality);
                }
                break;

            case 'gif':
                // Check GIF support is enabled
                if (imagetypes() & IMG_GIF) {
                    imagegif($image, $savePath);
                }
                break;

            case 'png':
                // Scale quality from 0-100 to 0-9
                $scaleQuality = round(($imageQuality / 100) * 9);

                // Invert quality setting as 0 is best, not 9
                $invertScaleQuality = 9 - $scaleQuality;

                // Check PNG support is enabled
                if (imagetypes() & IMG_PNG) {
                    imagepng($image, $savePath, $invertScaleQuality);
                }
                break;

            case 'webp':
                // Check WEBP support is enabled
                if (imagetypes() & IMG_WEBP) {
                    imagewebp($image, $savePath, $imageQuality);
                }
                break;

            default:
                throw new Exception(sprintf('Invalid image type: %s. Accepted types: jpg, gif, png, webp.', $extension));
                break;
        }

        // Remove the resource for the resized image
        imagedestroy($image);
    }

    /**
     * Open a file, detect its mime-type and create an image resource from it.
     * @param \Symfony\Component\HttpFoundation\File\File $file File instance
     * @return mixed
     * @throws Exception Thrown for invalid MIME type
     */
    protected function openImage($file)
    {
        $filePath = $file->getPathname();

        switch ($this->mime) {
            case 'image/jpeg':
                $img = @imagecreatefromjpeg($filePath);
                break;
            case 'image/gif':
                $img = @imagecreatefromgif($filePath);
                break;
            case 'image/png':
                $img = @imagecreatefrompng($filePath);
                $this->retainImageTransparency($img);
                break;
            case 'image/webp':
                $img = @imagecreatefromwebp($filePath);
                $this->retainImageTransparency($img);
                break;
            default:
                throw new Exception(sprintf('Invalid mime type: %s. Accepted types: image/jpeg, image/gif, image/png, image/webp.', $this->mime));
                break;
        }

        return $img;
    }

    /**
     * Return the image dimensions based on the option that was chosen.
     * @param int $newWidth The width of the image
     * @param int $newHeight The height of the image
     * @return array
     * @throws Exception Thrown for invalid dimension string
     */
    protected function getDimensions($newWidth, $newHeight)
    {
        $mode = $this->getOption('mode');

        switch ($mode) {
            case 'exact':
                return [$newWidth, $newHeight];

            case 'portrait':
                return [$this->getSizeByFixedHeight($newHeight), $newHeight];

            case 'landscape':
                return [$newWidth, $this->getSizeByFixedWidth($newWidth)];

            case 'auto':
                return $this->getSizeByAuto($newWidth, $newHeight);

            case 'crop':
                return $this->getOptimalCrop($newWidth, $newHeight);

            case 'fit':
                return $this->getSizeByFit($newWidth, $newHeight);

            default:
                throw new Exception('Invalid dimension type. Accepted types: exact, portrait, landscape, auto, crop, fit.');
        }
    }

    /**
     * Returns the width based on the image height
     * @param int $newHeight The height of the image
     * @return int
     */
    protected function getSizeByFixedHeight($newHeight)
    {
        $ratio = $this->width / $this->height;
        return $newHeight * $ratio;
    }

    /**
     * Returns the height based on the image width
     * @param int $newWidth The width of the image
     * @return int
     */
    protected function getSizeByFixedWidth($newWidth)
    {
        $ratio = $this->height / $this->width;
        return $newWidth * $ratio;
    }

    /**
     * Checks to see if an image is portrait or landscape and resizes accordingly.
     * @param int $newWidth  The width of the image
     * @param int $newHeight The height of the image
     * @return array
     */
    protected function getSizeByAuto($newWidth, $newHeight)
    {
        // Less than 1 pixel height and width? (revert to original)
        if ($newWidth <= 1 && $newHeight <= 1) {
            $newWidth = $this->width;
            $newHeight = $this->height;
        }
        elseif ($newWidth <= 1) {
            $newWidth = $this->getSizeByFixedHeight($newHeight);
        }
        // Less than 1 pixel height? (portrait)
        elseif ($newHeight <= 1) {
            $newHeight = $this->getSizeByFixedWidth($newWidth);
        }

        // Image to be resized is wider (landscape)
        if ($this->height < $this->width) {
            $optimalWidth = $newWidth;
            $optimalHeight = $this->getSizeByFixedWidth($newWidth);
        }
        // Image to be resized is taller (portrait)
        elseif ($this->height > $this->width) {
            $optimalWidth = $this->getSizeByFixedHeight($newHeight);
            $optimalHeight = $newHeight;
        }
        // Image to be resized is a square
        else {
            if ($newHeight < $newWidth) {
                $optimalWidth = $newWidth;
                $optimalHeight = $this->getSizeByFixedWidth($newWidth);
            }
            elseif ($newHeight > $newWidth) {
                $optimalWidth = $this->getSizeByFixedHeight($newHeight);
                $optimalHeight = $newHeight;
            }
            else {
                // Square being resized to a square
                $optimalWidth = $newWidth;
                $optimalHeight = $newHeight;
            }
        }

        return [$optimalWidth, $optimalHeight];
    }

    /**
     * Attempts to find the best way to crop. Whether crop is based on the
     * image being portrait or landscape.
     * @param int $newWidth  The width of the image
     * @param int $newHeight The height of the image
     * @return array
     */
    protected function getOptimalCrop($newWidth, $newHeight)
    {
        $heightRatio = $this->height / $newHeight;
        $widthRatio  = $this->width /  $newWidth;

        if ($heightRatio < $widthRatio) {
            $optimalRatio = $heightRatio;
        }
        else {
            $optimalRatio = $widthRatio;
        }

        $optimalHeight = round($this->height / $optimalRatio);
        $optimalWidth  = round($this->width  / $optimalRatio);

        return [$optimalWidth, $optimalHeight];
    }

    /**
     * Fit the image inside a bounding box using maximum width and height constraints.
     * @param int $maxWidth The maximum width of the image
     * @param int $maxHeight The maximum height of the image
     * @return array
     */
    protected function getSizeByFit($maxWidth, $maxHeight) {

        // Calculate the scaling ratios in order to get the target width and height
        $ratioW = $maxWidth / $this->width;
        $ratioH = $maxHeight / $this->height;

        // Select the ratio which makes the image fit inside the constraints
        $effectiveRatio = min($ratioW, $ratioH);

        // Calculate the final width and height according to this ratio
        $optimalWidth = round($this->width * $effectiveRatio);
        $optimalHeight = round($this->height * $effectiveRatio);

        return [$optimalWidth, $optimalHeight];
    }
}
