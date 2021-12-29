<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class RSS_Item_Caching_getID3_Podcast_HelperTest extends RSS_Item_getID3_Podcast_HelperTest
{

    public static function setUpBeforeClass(): void
    {
        RSS_Item_getID3_Podcast_HelperTest::setUpBeforeClass();
    }

    public function setUp(): void
    {
        parent::setUp();
        mkdir('temp');
    }

    public function newHelper()
    {
        return new Caching_getID3_Podcast_Helper('temp', new getID3_Podcast_Helper());
    }

    public function getid3_helper_mock()
    {
        $m = $this->getMockBuilder(getID3_Podcast_Helper::class)
                  ->setMethods(['appendToItem'])
                  ->getMock();
        return $m;
    }

    public function test_changed_file_is_rescanned()
    {
        $mp = new MyPodcast();
        $helper = $this->newHelper();
        $mp->addHelper($helper);

        copy('../fixtures/empty.mp3', './empty.mp3');
        $item = new Media_RSS_Item('empty.mp3');

        $mp->addRssItem($item);

        $content = $mp->generate();

        touch('empty.mp3', time()+10);

        // now do it again
        $mp = new MyPodcast();
        $mock = $this->getid3_helper_mock();
        $mock->expects($this->once())->method('appendToItem');

        $helper = new Caching_getID3_Podcast_Helper('temp', $mock);
        $mp->addHelper($helper);

        $item = new Media_RSS_Item('empty.mp3');

        $mp->addRssItem($item);

        $content = $mp->generate();
    }

    public function test_empty_rss_item()
    {
        $this->assertCount(0, glob('temp' . DIRECTORY_SEPARATOR . '*'));
        parent::test_empty_rss_item();
        $this->assertCount(1, glob('temp' . DIRECTORY_SEPARATOR . '*'));

        // now do it again, from the cache
        $mp = new MyPodcast();
        $mock = $this->getid3_helper_mock();
        $mock->expects($this->never())->method('appendToItem');

        $helper = new Caching_getID3_Podcast_Helper('temp', $mock);
        $mp->addHelper($helper);

        $item = new Media_RSS_Item('empty.mp3');

        $mp->addRssItem($item);

        $content = $mp->generate();

        $this->assertEquals('0:00', $item->getDuration());
        $this->assertEquals('', $item->getID3Title());
        $this->assertEquals('', $item->getID3Artist());
        $this->assertEquals('', $item->getID3Alnum());
        $this->assertEquals('', $item->getImage());
    }

    public function test_id3v1_artist_album_title()
    {
        $this->assertCount(0, glob('temp' . DIRECTORY_SEPARATOR . '*'));
        parent::test_id3v1_artist_album_title();
        $this->assertCount(1, glob('temp' . DIRECTORY_SEPARATOR . '*'));

        $mp = new MyPodcast();
        $mock = $this->getid3_helper_mock();
        $mock->expects($this->never())->method('appendToItem');

        $helper = new Caching_getID3_Podcast_Helper('temp', $mock);
        $mp->addHelper($helper);

        $item = new Media_RSS_Item('id3v1_artist_album_title.mp3');

        $mp->addRssItem($item);

        $content = $mp->generate();

        $this->assertEquals('0:00', $item->getDuration());
        $this->assertEquals('EXAMPLE3', $item->getID3Title());
        $this->assertEquals('ARTIST3', $item->getID3Artist());
        $this->assertEquals('ALBUM3', $item->getID3Album());
        $this->assertEquals('', $item->getImage());
    }

    public function tearDown(): void
    {
        parent::tearDown();
        rmrf('temp');
    }

    public static function tearDownAfterClass(): void
    {
        RSS_Item_getID3_Podcast_HelperTest::tearDownAfterClass();
    }
}
