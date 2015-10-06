<?php namespace October\Rain\Exception;

/**
 * This class represents an AJAX exception.
 * These are considered "smart errors" and will send http code 406,
 * so they can pass response contents.
 *
 * @package october\exception
 * @author Alexey Bobkov, Samuel Georges
 */
class AjaxException extends ExceptionBase
{

    /**
     * @var array Collection response contents.
     */
    protected $contents;

    /**
     * Constructor.
     */
    public function __construct($contents)
    {
        if (is_string($contents)) {
            $contents = ['result' => $contents];
        }

        $this->contents = $contents;

        parent::__construct(json_encode($contents));
    }

    /**
     * Returns invalid fields.
     */
    public function getContents()
    {
        return $this->contents;
    }

}
