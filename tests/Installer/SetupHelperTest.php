<?php

use October\Rain\Installer\Traits\SetupHelper;

class SetupHelperTest extends TestCase
{
    /**
     * @var string gitignore is a temporary file path
     */
    protected $gitignore;

    public function setUp(): void
    {
        $this->gitignore = tempnam(sys_get_temp_dir(), 'gitignore');
    }

    public function tearDown(): void
    {
        @unlink($this->gitignore);
    }

    public function testUpdateGitignore()
    {
        $result = $this->updateGitignore(implode("\n", [
            '# Common',
            'composer.phar',
            'composer.lock',
            'package-lock.json',
            '/modules/system/node_modules',
            '',
            '# Project',
            '/vendor',
            '',
        ]));

        $this->assertEquals(implode("\n", [
            '# Common',
            'composer.phar',
            '/modules/system/node_modules',
            '',
            '# Project',
            '/vendor',
            '/modules',
            '',
        ]), $result);
    }

    public function testUpdateGitignoreRepeated()
    {
        $this->updateGitignore("/vendor\ncomposer.lock\n");
        $this->updateGitignore(file_get_contents($this->gitignore));
        $result = $this->updateGitignore(file_get_contents($this->gitignore));

        $this->assertEquals("/vendor\n/modules\n", $result);
    }

    public function testUpdateGitignoreWindowsLineEndings()
    {
        $result = $this->updateGitignore("/vendor\r\ncomposer.lock\r\n");
        $this->assertEquals("/vendor\r\n/modules\r\n", $result);

        $result = $this->updateGitignore($result);
        $this->assertEquals("/vendor\r\n/modules\r\n", $result);
    }

    public function testUpdateGitignoreLockVariations()
    {
        $result = $this->updateGitignore("/modules\n/composer.lock\n/vendor\n");
        $this->assertEquals("/modules\n/vendor\n", $result);

        $result = $this->updateGitignore("/modules\ncomposer.lock");
        $this->assertEquals("/modules\n", $result);

        $result = $this->updateGitignore("/modules\ncomposer.lock.bak\n");
        $this->assertEquals("/modules\ncomposer.lock.bak\n", $result);

        $result = $this->updateGitignore("/modules\n/package-lock.json\npackage-lock.json.bak\n");
        $this->assertEquals("/modules\npackage-lock.json.bak\n", $result);
    }

    /**
     * updateGitignore writes the contents to the gitignore file, updates
     * it using the trait and returns the resulting contents
     */
    protected function updateGitignore(string $contents): string
    {
        file_put_contents($this->gitignore, $contents);

        $helper = new class {
            use SetupHelper;
        };

        self::callProtectedMethod($helper, 'updateGitignore', [$this->gitignore]);

        return file_get_contents($this->gitignore);
    }
}
