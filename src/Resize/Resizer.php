<?php namespace October\Rain\Resize;

use Intervention\Image\Interfaces\ImageInterface;
use Symfony\Component\HttpFoundation\File\File as FileObj;
use Exception;

/**
 * Resizer for images
 *
 * Available options are:
 *  - mode: Either exact, portrait, landscape, auto, cover or crop.
 *  - offset: The offset of the crop = [ left, top ]
 *  - sharpen: Sharpen image, from 0 - 100 (default: 0)
 *  - interlace: Interlace image,  Boolean: false (disabled: default), true (enabled)
 *  - quality: Image quality, from 0 - 100 (default: 90)
 *
 * @package october\resize
 * @author Alexey Bobkov, Samuel Georges
 */
class Resizer
{
    /**
     * @var FileObj file the symfony uploaded file object
     */
    protected $file;

    /**
     * @var string extension of the uploaded file
     */
    protected $extension;

    /**
     * @var string mime type of the uploaded file
     */
    protected $mime;

    /**
     * @var \GdImage image (on disk) that's being resized
     */
    protected $image;

    /**
     * @var \GdImage originalImage cached
     */
    protected $originalImage;

    /**
     * @var int width of the original image being resized
     */
    protected $width;

    /**
     * @var int height of the original image being resized
     */
    protected $height;

    /**
     * @var int|null orientation (Exif) of image
     */
    protected $orientation;

    /**
     * @var array options used for resizing
     */
    protected $options = [];


    /**
     * __construct instantiates the Resizer and receives the path to an image we're working with.
     * The file can be either Input::file('field_name') or a path to a file
     * @param mixed $file
     */
    public function __construct($file)
    {
        if (!$file) {
            throw new Exception('Opened resizer on an empty file');
        }

        if (is_string($file)) {
            $file = new FileObj($file);
        }

        $this->file = $file;

        // Get the file extension
        $this->extension = $file->guessExtension();
        $this->mime = $file->getMimeType();

        // Open up the file
        $this->image = $this->openImage($file);

        // Get width and height of our image
        $this->width  = $this->image->width();
        $this->height = $this->image->height();

        // Set default options
        $this->setOptions([]);
    }

    /**
     * open is a static constructor
     */
    public static function open($file): Resizer
    {
        return new Resizer($file);
    }

    /**
     * setOptions sets resizer options
     */
    public function setOptions(array $options): static
    {
        $this->options = array_merge([
            'mode' => 'auto',
            'offset' => [0, 0],
            'sharpen' => 0,
            'interlace' => false,
            'quality' => 90
        ], $options);

        return $this;
    }

    /**
     * getOption gets an individual resizer option
     * @param string $option
     */
    protected function getOption($option)
    {
        return $this->options[$option] ?? null;
    }

    /**
     * openImage opens a file, detect its mime-type and create an image resource from it
     * @param \Symfony\Component\HttpFoundation\File\File $file
     * @return mixed
     */
    protected function openImage($file): ImageInterface
    {
        $filePath = $file->getPathname();

        $driver = new \Intervention\Image\Drivers\Gd\Driver;

        $manager = new \Intervention\Image\ImageManager($driver);

        return $manager->read($filePath);
    }

    /**
     * reset the image back to the original.
     */
    public function reset(): static
    {
        $this->image = $this->openImage($this->file);

        return $this;
    }

    /**
     * save the image based on its file type.
     * @param string $savePath
     */
    public function save($savePath)
    {
        $this->image->save($savePath);
    }

    /**
     * resize and/or crop an image, specifying the new width and height of the
     * destination image.
     * @param int|null $width
     * @param int|null $height
     * @param array $options
     */
    public function resize($width, $height, $options = []): static
    {
        $this->setOptions($options);

        // Support null for proportional resizing
        $width = (int) $width;
        $height = (int) $height;

        if (!$width && !$height) {
            $width = $this->width;
            $height = $this->height;
        }
        elseif (!$width) {
            $width = (int) round($height * ($this->width / $this->height));
        }
        elseif (!$height) {
            $height = (int) round($width * ($this->height / $this->width));
        }

        $mode = $this->options['mode'] ?? 'auto';

        if ($mode === 'exact') {
            $this->image->resize($width, $height);
        }
        elseif ($mode === 'crop') {
            // Backward compatibility
            if (!is_array($options['offset'] ?? null)) {
                $this->image->cover($width, $height);
            }
            else {
                $this->image->crop(
                    $width,
                    $height,
                    $this->options['offset'][0] ?? ($this->options['offset']['x'] ?? 0),
                    $this->options['offset'][1] ?? ($this->options['offset']['y'] ?? 0)
                );
            }
        }
        elseif ($mode === 'cover' || $mode === 'fit') {
            $this->image->cover($width, $height);
        }
        elseif ($mode === 'auto') {
            $this->image->scale($width, $height);
        }
        elseif ($mode === 'portrait') {
            $this->image->scale(null, $height);
        }
        elseif ($mode === 'landscape') {
            $this->image->scale($width, null);
        }

        return $this;
    }
}
