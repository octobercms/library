<?php namespace October\Rain\Composer\Concerns;

use Composer\IO\NullIO;
use Composer\IO\BufferIO;
use Composer\IO\ConsoleIO;
use Composer\IO\IOInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Command\Command;

/**
 * HasOutput for composer
 *
 * @package october\composer
 * @author Alexey Bobkov, Samuel Georges
 */
trait HasOutput
{
    /**
     * @var IOInterface output
     */
    protected $output;

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
     * setOutputCommand
     */
    public function setOutputCommand(Command $command, InputInterface $input)
    {
        $this->setOutput(new ConsoleIO($input, $command->getOutput(), $command->getHelperSet()));
    }

    /**
     * setOutputBuffer
     */
    public function setOutputBuffer()
    {
        $this->setOutput(new BufferIO());
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
