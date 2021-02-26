<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class RSS_Item_getID3_Podcast_Helper_AUTO_SAVE_COVERTest extends RSS_Item_getID3_Podcast_HelperTest
{


    public static function setUpBeforeClass(): void
    {
        RSS_Item_getID3_Podcast_HelperTest::setUpBeforeClass();
        getID3_Podcast_Helper::$AUTO_SAVE_COVER_ART = true;
    }

    public function test_mp4_tagged_cover()
    {
        $mp = new MyPodcast();
        $helper = new getID3_Podcast_Helper();
        $mp->addHelper($helper);

        copy('../fixtures/tagged_with_cover.mp4', './tagged_with_cover.mp4');
        $item = new Media_RSS_Item('tagged_with_cover.mp4');

        $mp->addRssItem($item);

        $content = $mp->generate();

        $this->assertEquals('http://www.example.com/mp3/tagged_with_cover.jpg', $item->getImage());
        $this->assertTrue(file_exists('tagged_with_cover.jpg'));
        $this->assertEquals(file_get_contents('tagged_with_cover.jpg'), file_get_contents('../fixtures/empty.jpg'));
    }

    public function test_id3v2_artist_album_title_with_cover()
    {
        $mp = new MyPodcast();
        $helper = new getID3_Podcast_Helper();
        $mp->addHelper($helper);

        copy('../fixtures/id3v2_artist_album_title_cover.mp3', './id3v2_artist_album_title_cover.mp3');
        $item = new Media_RSS_Item('id3v2_artist_album_title_cover.mp3');

        $mp->addRssItem($item);

        $content = $mp->generate();

        $this->assertEquals('http://www.example.com/mp3/id3v2_artist_album_title_cover.jpg', $item->getImage());
        $this->assertTrue(file_exists('id3v2_artist_album_title_cover.jpg'));
        $this->assertEquals(file_get_contents('id3v2_artist_album_title_cover.jpg'), file_get_contents('../fixtures/empty.jpg'));
    }

    public static function tearDownAfterClass(): void
    {
        chdir('..');
    }
}
