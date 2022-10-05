<?php namespace October\Rain\Composer\Concerns;

/**
 * HasOctoberCommands for composer
 *
 * @package october\composer
 * @author Alexey Bobkov, Samuel Georges
 */
trait HasOctoberCommands
{
    /**
     * addOctoberRepository
     */
    public function addOctoberRepository(string $url)
    {
        $this->addRepository(
            'octobercms',
            'composer',
            $url,
            [
                'only' => ['october/*', '*-plugin', '*-theme']
            ]
        );
    }
}
