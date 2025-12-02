<?php namespace October\Rain\Exception;

use Larajax\Contracts\AjaxExceptionInterface;

/**
 * AjaxException is considered a "smart error" and will send http code 406,
 * so they can pass response contents.
 *
 * @package october\exception
 * @author Alexey Bobkov, Samuel Georges
 */
class AjaxException extends ExceptionBase implements AjaxExceptionInterface
{
    /**
     * @var array contents of the response.
     */
    protected $contents;

    /**
     * __construct the exception
     */
    public function __construct($contents = null)
    {
        if (is_string($contents)) {
            $contents = ['result' => $contents];
        }
        elseif (!is_array($contents)) {
            $contents = [];
        }

        $this->contents = $contents;

        parent::__construct(json_encode($contents));
    }

    /**
     * getContents returns invalid fields.
     */
    public function getContents()
    {
        return $this->contents;
    }

    /**
     * addContent is used to add extra data to an AJAX exception
     */
    public function addContent(string $key, $val)
    {
        $this->contents[$key] = $val;
    }

    /**
     * toAjaxData
     */
    public function toAjaxData(): array
    {
        return (array) $this->contents;
    }
}
