<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class RSS_Item_getID3_Podcast_HelperTest extends TestCase
{

    public static function setUpBeforeClass(): void
    {
        prepare_testing_dir();
        getID3_Podcast_Helper::$AUTO_SAVE_COVER_ART = false;
        RSS_File_Item::$FILES_URL = 'http://www.example.com/mp3/';
        RSS_File_Item::$FILES_DIR = getcwd();
    }

    public function newHelper()
    {
        return new getID3_Podcast_Helper();
    }

    public function test_empty_rss_item()
    {
        $mp = new MyPodcast();
        $helper = $this->newHelper();
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
        $helper = $this->newHelper();
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
        $helper = $this->newHelper();
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
        $helper = $this->newHelper();
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
        $helper = $this->newHelper();
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
        $helper = $this->newHelper();
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

    public function test_id3v2_artist_album_title_with_cover()
    {
        $mp = new MyPodcast();
        $helper = $this->newHelper();
        $mp->addHelper($helper);

        copy('../fixtures/id3v2_artist_album_title_cover.mp3', './id3v2_artist_album_title_cover.mp3');
        $item = new Media_RSS_Item('id3v2_artist_album_title_cover.mp3');

        $mp->addRssItem($item);

        $content = $mp->generate();

        $this->assertEquals('', $item->getImage());
        $this->assertFalse(file_exists('id3v2_artist_album_title_cover.jpg'));
    }    

    public function test_id3v2_artist_title()
    {
        $mp = new MyPodcast();
        $helper = $this->newHelper();
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
        $helper = $this->newHelper();
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
        $helper = $this->newHelper();
        $mp->addHelper($helper);

        copy('../fixtures/id3v2_comment.mp3', './id3v2_comment.mp3');
        $item = new Media_RSS_Item('id3v2_comment.mp3');

        $mp->addRssItem($item);

        $content = $mp->generate();

        $this->assertEquals('COMMENT8', $item->getID3Comment());
    }

    public function test_mp4_empty()
    {
        $mp = new MyPodcast();
        $helper = $this->newHelper();
        $mp->addHelper($helper);

        copy('../fixtures/empty.mp4', './empty.mp4');
        $item = new Media_RSS_Item('empty.mp4');

        $mp->addRssItem($item);

        $content = $mp->generate();

        $this->assertEquals('0:00', $item->getDuration());
        $this->assertEquals('', $item->getID3Title());
        $this->assertEquals('', $item->getID3Artist());
        $this->assertEquals('', $item->getID3Album());
        $this->assertEquals('', $item->getImage());
    }

    public function test_mp4_tagged()
    {
        $mp = new MyPodcast();
        $helper = $this->newHelper();
        $mp->addHelper($helper);

        copy('../fixtures/tagged.mp4', './tagged.mp4');
        $item = new Media_RSS_Item('tagged.mp4');

        $mp->addRssItem($item);

        $content = $mp->generate();

        $this->assertEquals('0:00', $item->getDuration());
        $this->assertEquals('TTT', $item->getID3Title());
        $this->assertEquals('AAA', $item->getID3Artist());
        $this->assertEquals('ALAL', $item->getID3Album());
        $this->assertEquals('CCC', $item->getID3Comment());
        $this->assertEquals('', $item->getImage());
    }

    public function test_mp4_tagged_cover()
    {
        $mp = new MyPodcast();
        $helper = $this->newHelper();
        $mp->addHelper($helper);

        copy('../fixtures/tagged_with_cover.mp4', './tagged_with_cover.mp4');
        $item = new Media_RSS_Item('tagged_with_cover.mp4');

        $mp->addRssItem($item);

        $content = $mp->generate();

        $this->assertEquals('', $item->getImage());
        $this->assertFalse(file_exists('tagged_with_cover.jpg'));
    }

    public function tearDown(): void
    {
        file_exists('empty.mp4') && unlink('empty.mp4');
        file_exists('tagged.mp4') && unlink('tagged.mp4');
        file_exists('tagged_with_cover.mp4') && unlink('tagged_with_cover.mp4');
        file_exists('tagged_with_cover.jpg') && unlink('tagged_with_cover.jpg');
        file_exists('empty.mp3') && unlink('empty.mp3');
        file_exists('id3v1_artist_album_title.mp3') && unlink('id3v1_artist_album_title.mp3');
        file_exists('id3v1_artist_title.mp3') && unlink('id3v1_artist_title.mp3');
        file_exists('id3v1_title.mp3') && unlink('id3v1_title.mp3');
        file_exists('id3v2_artist_album_title.mp3') && unlink('id3v2_artist_album_title.mp3');
        file_exists('id3v2_artist_album_title_cover.mp3') && unlink('id3v2_artist_album_title_cover.mp3');
        file_exists('id3v2_artist_album_title_cover.jpg') && unlink('id3v2_artist_album_title_cover.jpg');
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
