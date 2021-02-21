<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class RSS_Item_getID3_Podcast_HelperTest extends TestCase
{

    public static function setUpBeforeClass(): void
    {
        define('AUTO_SAVE_COVER_ART', false);
        RSS_File_Item::$FILES_URL = 'http://www.example.com/mp3/';
        RSS_File_Item::$FILES_DIR = getcwd();
        prepare_testing_dir();
    }

    public function test_empty_rss_item()
    {
        $mp = new MyPodcast();
        $helper = new getID3_Podcast_Helper();
        $mp->addHelper($helper);

        copy('../fixtures/empty.mp3', './empty.mp3');
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
        $mp = new MyPodcast();
        $helper = new getID3_Podcast_Helper();
        $mp->addHelper($helper);

        copy('../fixtures/id3v1_artist_album_title.mp3', './id3v1_artist_album_title.mp3');
        $item = new Media_RSS_Item('id3v1_artist_album_title.mp3');

        $mp->addRssItem($item);

        $content = $mp->generate();

        $this->assertEquals('0:00', $item->getDuration());
        $this->assertEquals('EXAMPLE3', $item->getID3Title());
        $this->assertEquals('ARTIST3', $item->getID3Artist());
        $this->assertEquals('ALBUM3', $item->getID3Album());
        $this->assertEquals('', $item->getImage());
    }

    public function test_id3v1_artist_title()
    {
        $mp = new MyPodcast();
        $helper = new getID3_Podcast_Helper();
        $mp->addHelper($helper);

        copy('../fixtures/id3v1_artist_title.mp3', './id3v1_artist_title.mp3');
        $item = new Media_RSS_Item('id3v1_artist_title.mp3');

        $mp->addRssItem($item);

        $content = $mp->generate();

        $this->assertEquals('0:00', $item->getDuration());
        $this->assertEquals('EXAMPLE2', $item->getID3Title());
        $this->assertEquals('ARTIST2', $item->getID3Artist());
        $this->assertEquals('', $item->getID3Album());
        $this->assertEquals('', $item->getImage());
    }

    public function test_id3v1_title()
    {
        $mp = new MyPodcast();
        $helper = new getID3_Podcast_Helper();
        $mp->addHelper($helper);

        copy('../fixtures/id3v1_title.mp3', './id3v1_title.mp3');
        $item = new Media_RSS_Item('id3v1_title.mp3');

        $mp->addRssItem($item);

        $content = $mp->generate();

        $this->assertEquals('0:00', $item->getDuration());
        $this->assertEquals('EXAMPLE1', $item->getID3Title());
        $this->assertEquals('', $item->getID3Artist());
        $this->assertEquals('', $item->getID3Album());
        $this->assertEquals('', $item->getImage());
    }

    public function test_id3v1_comment()
    {
        $mp = new MyPodcast();
        $helper = new getID3_Podcast_Helper();
        $mp->addHelper($helper);

        copy('../fixtures/id3v1_comment.mp3', './id3v1_comment.mp3');
        $item = new Media_RSS_Item('id3v1_comment.mp3');

        $mp->addRssItem($item);

        $content = $mp->generate();

        $this->assertEquals('COMMENT4', $item->getID3Comment());
    }

    public function test_id3v2_artist_album_title()
    {
        $mp = new MyPodcast();
        $helper = new getID3_Podcast_Helper();
        $mp->addHelper($helper);

        copy('../fixtures/id3v2_artist_album_title.mp3', './id3v2_artist_album_title.mp3');
        $item = new Media_RSS_Item('id3v2_artist_album_title.mp3');

        $mp->addRssItem($item);

        $content = $mp->generate();

        $this->assertEquals('0:00', $item->getDuration());
        $this->assertEquals('EXAMPLE7', $item->getID3Title());
        $this->assertEquals('ARTIST7', $item->getID3Artist());
        $this->assertEquals('ALBUM7', $item->getID3Album());
        $this->assertEquals('', $item->getImage());
    }

    public function test_id3v2_artist_title()
    {
        $mp = new MyPodcast();
        $helper = new getID3_Podcast_Helper();
        $mp->addHelper($helper);

        copy('../fixtures/id3v2_artist_title.mp3', './id3v2_artist_title.mp3');
        $item = new Media_RSS_Item('id3v2_artist_title.mp3');

        $mp->addRssItem($item);

        $content = $mp->generate();

        $this->assertEquals('0:00', $item->getDuration());
        $this->assertEquals('EXAMPLE6', $item->getID3Title());
        $this->assertEquals('ARTIST6', $item->getID3Artist());
        $this->assertEquals('', $item->getID3Album());
        $this->assertEquals('', $item->getImage());
    }

    public function test_id3v2_title()
    {
        $mp = new MyPodcast();
        $helper = new getID3_Podcast_Helper();
        $mp->addHelper($helper);

        copy('../fixtures/id3v2_title.mp3', './id3v2_title.mp3');
        $item = new Media_RSS_Item('id3v2_title.mp3');

        $mp->addRssItem($item);

        $content = $mp->generate();

        $this->assertEquals('0:00', $item->getDuration());
        $this->assertEquals('EXAMPLE5', $item->getID3Title());
        $this->assertEquals('', $item->getID3Artist());
        $this->assertEquals('', $item->getID3Album());
        $this->assertEquals('', $item->getImage());
    }

    public function test_id3v2_comment()
    {
        $mp = new MyPodcast();
        $helper = new getID3_Podcast_Helper();
        $mp->addHelper($helper);

        copy('../fixtures/id3v2_comment.mp3', './id3v2_comment.mp3');
        $item = new Media_RSS_Item('id3v2_comment.mp3');

        $mp->addRssItem($item);

        $content = $mp->generate();

        $this->assertEquals('COMMENT8', $item->getID3Comment());
    }

    public function tearDown(): void
    {
        file_exists('empty.mp3') && unlink('empty.mp3');
        file_exists('id3v1_artist_album_title.mp3') && unlink('id3v1_artist_album_title.mp3');
        file_exists('id3v1_artist_title.mp3') && unlink('id3v1_artist_title.mp3');
        file_exists('id3v1_title.mp3') && unlink('id3v1_title.mp3');
        file_exists('id3v2_artist_album_title.mp3') && unlink('id3v2_artist_album_title.mp3');
        file_exists('id3v2_artist_title.mp3') && unlink('id3v2_artist_title.mp3');
        file_exists('id3v2_title.mp3') && unlink('id3v2_title.mp3');
        file_exists('id3v1_comment.mp3') && unlink('id3v1_comment.mp3');
        file_exists('id3v2_comment.mp3') && unlink('id3v2_comment.mp3');
    }

    public static function tearDownAfterClass(): void
    {
        chdir('..');
    }
}
