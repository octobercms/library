<?php namespace October\Rain\Assetic\Exception;

/*
 * This file is part of the Assetic package, an OpenSky project.
 *
 * (c) 2010-2014 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Describes an exception that occurred within a filter.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class FilterException extends \RuntimeException implements Exception
{
    private $originalMessage;
    private $input;

    public function __construct($message, $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->originalMessage = $message;
    }

    public function setInput($input)
    {
        $this->input = $input;
        $this->updateMessage();

        return $this;
    }

    public function getInput()
    {
        return $this->input;
    }

    private function updateMessage()
    {
        $message = $this->originalMessage;

        if (!empty($this->input)) {
            $message .= "\n\nInput:\n".$this->input;
        }

        $this->message = $message;
    }
}
