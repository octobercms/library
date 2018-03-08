<?php

use October\Rain\Database\Attach\Resizer;

class ResizerTest extends TestCase
{
    // Fixture base paths
    const FIXTURE_PATH = __DIR__ . '/../../fixtures/';
    const FIXTURE_SRC_BASE_PATH = self::FIXTURE_PATH . 'resizer/source/';
    const FIXTURE_TARGET_PATH = self::FIXTURE_PATH . 'resizer/target/';

    // Source image filenames
    const SRC_LANDSCAPE_ROTATED = 'landscape_rotated.jpg';
    const SRC_LANDSCAPE_TRANSPARENT = 'landscape_transparent.png';
    const SRC_PORTRAIT = 'portrait.jpg';
    const SRC_SQUARE = 'square.jpg';

    /** @var string */
    protected $source;

    /** @var Resizer */
    protected $resizer;

    public function testReset()
    {
    }

    public function testSetOptions()
    {
    }

    public function testResizeAutoPortrait()
    {
    }

    public function testResizeAutoLandscape()
    {
        $this->source = self::SRC_LANDSCAPE_TRANSPARENT;
        $this->createFixtureResizer();
        $this->resizer->resize(25, 50, ['mode' => 'auto']);
        $this->assertImageSameAsFixture(__METHOD__);
    }

    public function testResizeAutoSquare()
    {
    }

    /*
     * TODO add many resize test cases
     */

    public function testSharpen()
    {
    }

    public function testCrop()
    {
    }

    /**
     * Create the Resizer instance from the declared source image.
     * @throws Exception
     */
    protected function createFixtureResizer()
    {
        $this->resizer = new Resizer(self::FIXTURE_SRC_BASE_PATH . $this->source);
    }

    /**
     * Build the full path to the target fixture from a test method name.
     * @param string $methodName Method name
     * @return string Full path to target fixture
     */
    protected function buildTargetFixturePath(string $methodName)
    {
        $extension = pathinfo($this->source, PATHINFO_EXTENSION);
        $filename = str_replace(__CLASS__ . '::', '', $methodName);
        return self::FIXTURE_TARGET_PATH . $filename . '.' . $extension;
    }

    /**
     * Assert that the current resizer image, once saved, is the same as the fixture which corresponds to the given
     * method name.
     * @param string $methodName Method name
     * @throws Exception
     */
    protected function assertImageSameAsFixture(string $methodName)
    {
        // For now, generate target fixture
        $this->resizer->save($this->buildTargetFixturePath($methodName));
    }

}
