<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class Media_RSS_ItemTest extends RSS_File_ItemTest
{
    protected $mtime;

    public static function setUpBeforeClass(): void
    {
        RSS_File_ItemTest::setUpBeforeClass();
        Media_RSS_Item::$LONG_TITLES = false;
        defined('DESCRIPTION_SOURCE') || define('DESCRIPTION_SOURCE', 'comment');
    }

    public function getMediaFileContent()
    {
        return 'x';
    }

    public function getMediaFileLength()
    {
        return 1;
    }

    // ID3 / tag data is typically set by getID3_Podcast_Helper, which is not under test here.

    public function getID3Artist()
    {
        return '';
    }

    public function getID3Album()
    {
        return '';
    }

    public function getID3Title()
    {
        return '';
    }

    public function getID3Comment()
    {
        return '';
    }

    public function newRSSItem()
    {
        // default tests are conducted with an empty file (which, therefore, has no ID3 tags to read)
        file_put_contents($this->filename, $this->getMediaFileContent());

        // ensure that tests do not fail when getting unlucky with when the clock ticks.
        $this->mtime = time();
        touch($this->filename, $this->mtime);

        $item = new Media_RSS_Item($this->filename);
        $item->setID3Album($this->getID3Album());
        $item->setID3Title($this->getID3Title());
        $item->setID3Artist($this->getID3Artist());
        $item->setID3Comment($this->getID3Comment());
        return $item;
    }

    public function test_constructor_sets_default_properties_from_file_metadata()
    {
        $item = $this->newRSSItem();
        $this->assertEquals($this->getMediaFileLength(), $item->getLength());
        $this->assertEquals(date('r', $this->mtime), $item->getPubDate());
    }

    public function test_description_from_comment_tag()
    {
        $item = $this->newRSSItem();
        $this->assertEquals($this->getID3Comment(), $item->getDescription());
    }

    /**
     * For Media RSS files, the default summary is ID3 the description, which is in turn the ID3 comment
     * @override
     */
    public function test_summary_default() {
        $item = $this->newRSSItem();
        $this->assertEquals($this->getID3Comment(), $item->getSummary());
    }

    public function tearDown(): void
    {
        file_exists($this->filename) && unlink($this->filename);
        parent::tearDown();
    }
}
