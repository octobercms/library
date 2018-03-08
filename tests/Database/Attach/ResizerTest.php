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

    protected $resizer;

    /**
     * @return Resizer
     * @throws Exception
     */
    protected static function getLandscapeRotatedFixture() {
        return new Resizer(self::FIXTURE_SRC_BASE_PATH . self::SRC_LANDSCAPE_ROTATED);
    }

    public function testReset()
    {
    }

    public function testSetOptions()
    {
    }

    public function testGetOrientationNonRotated()
    {
    }

    public function testGetOrientationRotated()
    {
    }

    public function testdGetWidthNonRotated()
    {
    }

    public function testGetWidthRotated()
    {
    }

    public function testGetHeightNonRotated()
    {
    }

    public function testGetHeightRotated()
    {
    }

    public function testGetRotatedOriginalNonRotated()
    {
    }

    /**
     * @throws Exception
     */
    public function testResizeAutoPortrait()
    {
    }

    /**
     * @throws Exception
     */
    public function testResizeAutoLandscape()
    {
        $this->resizer = self::getLandscapeRotatedFixture();
        $this->assertImageSameAsFixture(__METHOD__);
    }

    public function testResizeAutoSquare()
    {
    }

    public function testSharpen()
    {
    }

    public function testCrop()
    {
    }

    /**
     * @param $name
     * @throws Exception
     */
    protected function assertImageSameAsFixture($name)
    {
        // For now, generate target fixture
        $this->resizer->save(self::FIXTURE_TARGET_PATH, $name);
    }
}
