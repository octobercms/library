<?php namespace October\Rain\Composer;

use Composer\IO\IOInterface;
use Composer\IO\NullIO;
use Composer\IO\BufferIO;

/**
 * HasOutput for composer
 *
 * @package october\composer
 * @author Alexey Bobkov, Samuel Georges
 */
trait HasOutput
{
    /**
     * setOutput
     */
    public function setOutput(IOInterface $output = null)
    {
        if ($output === null) {
            $this->output = new NullIO();
        }
        else {
            $this->output = $output;
        }
    }

    /**
     * setOutputAsBuffer
     */
    public function setOutputBuffer()
    {
        $this->output = new BufferIO();
    }

    /**
     * getOutputBuffer
     */
    public function getOutputBuffer(): string
    {
        if ($this->output instanceof BufferIO) {
            return $this->output->getOutput();
        }

        return '';
    }
}
