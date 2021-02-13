<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class Media_RSS_ItemTest extends RSS_File_ItemTest
{
    private $mtime;

    public static function setUpBeforeClass(): void
    {
        RSS_File_ItemTest::setUpBeforeClass();
        defined('LONG_TITLES') || define('LONG_TITLES', false);
        defined('DESCRIPTION_SOURCE') || define('DESCRIPTION_SOURCE', 'comment');
    }

    public function newRSSItem()
    {
        // default tests are conducted with an empty file (which, therefore, has no ID3 tags to read)
        file_put_contents('example.mp3', 'x');

        // ensure that tests do not fail when getting unlucky with when the clock ticks.
        $this->mtime = time();
        touch('example.mp3', $this->mtime);

        return new Media_RSS_Item('example.mp3');
    }

    public function test_constructor_sets_default_properties_from_file_metadata()
    {
        $item = $this->newRSSItem();
        $this->assertEquals('1', $item->getLength());
        $this->assertEquals(date('r', $this->mtime), $item->getPubDate());
    }

    public function tearDown(): void
    {
        file_exists('example.mp3') && unlink('example.mp3');
        parent::tearDown();
    }
}
