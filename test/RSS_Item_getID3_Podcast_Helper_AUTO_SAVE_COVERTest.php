<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class RSS_Item_getID3_Podcast_Helper_AUTO_SAVE_COVERTest extends RSS_Item_getID3_Podcast_HelperTest
{


    public static function setUpBeforeClass(): void
    {
        RSS_Item_getID3_Podcast_HelperTest::setUpBeforeClass();
        getID3_Podcast_Helper::$AUTO_SAVE_COVER_ART = true;
        // Dir_Podcast::$RECURSIVE_DIRECTORY_ITERATOR = true;
    }

    public function test_mp4_tagged_cover()
    {
        $mp = new MyPodcast();
        $helper = new getID3_Podcast_Helper();
        $mp->addHelper($helper);

        mkdir('subdir');
        copy('../fixtures/tagged_with_cover.mp4', './tagged_with_cover.mp4');
        copy('../fixtures/tagged_with_cover.mp4', './subdir/tagged_with_cover2.mp4');
        $item = new Media_RSS_Item(RSS_File_Item::$FILES_DIR . '/tagged_with_cover.mp4');
        $item2 = new Media_RSS_Item(RSS_File_Item::$FILES_DIR . '/subdir/tagged_with_cover2.mp4');

        $mp->addRssItem($item);
        $mp->addRssItem($item2);

        $content = $mp->generate();

        $this->assertEquals('http://www.example.com/mp3/tagged_with_cover.jpg', $item->getImage());
        $this->assertEquals('http://www.example.com/mp3/subdir/tagged_with_cover2.jpg', $item2->getImage());
        $this->assertTrue(file_exists('tagged_with_cover.jpg'));
        $this->assertTrue(file_exists('subdir/tagged_with_cover2.jpg'));
        $this->assertEquals(file_get_contents('tagged_with_cover.jpg'), file_get_contents('../fixtures/empty.jpg'));
        $this->assertEquals(file_get_contents('subdir/tagged_with_cover2.jpg'), file_get_contents('../fixtures/empty.jpg'));
    }

    public function test_id3v2_artist_album_title_with_cover()
    {
        $mp = new MyPodcast();
        $helper = new getID3_Podcast_Helper();
        $mp->addHelper($helper);

        mkdir('another.subdir');
        copy('../fixtures/id3v2_artist_album_title_cover.mp3', './id3v2_artist_album_title_cover.mp3');
        copy('../fixtures/id3v2_artist_album_title_cover.mp3', './another.subdir/id3v2_artist_album_title_cover.mp3');
        $item = new Media_RSS_Item('id3v2_artist_album_title_cover.mp3');
        $item2 = new Media_RSS_Item('another.subdir/id3v2_artist_album_title_cover.mp3');

        $mp->addRssItem($item);
        $mp->addRssItem($item2);

        $content = $mp->generate();

        $this->assertEquals('http://www.example.com/mp3/id3v2_artist_album_title_cover.jpg', $item->getImage());
        $this->assertEquals('http://www.example.com/mp3/another.subdir/id3v2_artist_album_title_cover.jpg', $item2->getImage());
        $this->assertTrue(file_exists('id3v2_artist_album_title_cover.jpg'));
        $this->assertTrue(file_exists('another.subdir/id3v2_artist_album_title_cover.jpg'));
        $this->assertEquals(file_get_contents('id3v2_artist_album_title_cover.jpg'), file_get_contents('../fixtures/empty.jpg'));
        $this->assertEquals(file_get_contents('another.subdir/id3v2_artist_album_title_cover.jpg'), file_get_contents('../fixtures/empty.jpg'));
    }

    public function test_auto_save_doesnt_create_spurious_helper_duplication()
    {
        define('CLI_ONLY', true);

        copy('../fixtures/id3v2_artist_album_title_cover.mp3', './id3v2_artist_album_title_cover.mp3');

        mkdir('temp');
        $mp = new Cached_Dir_Podcast('.', 'temp');
        $mp->init();
        $getid3 = $mp->addHelper(new Caching_getID3_Podcast_Helper('temp', new getID3_Podcast_Helper()));
        $atom   = $mp->addHelper(new Atom_Podcast_Helper());
        $itunes = $mp->addHelper(new iTunes_Podcast_Helper());
        $content = $mp->generate();

        # checking for duplication
        $this->assertEquals(1, preg_match_all("/<\/itunes:duration>/", $content));
        
        age_dir_by('.', 60);

        $mp = new Cached_Dir_Podcast('.', 'temp');
        $mp->init();
        $getid3 = $mp->addHelper(new Caching_getID3_Podcast_Helper('temp', new getID3_Podcast_Helper()));
        $atom   = $mp->addHelper(new Atom_Podcast_Helper());
        $itunes = $mp->addHelper(new iTunes_Podcast_Helper());
        $content = $mp->generate();

        # checking for duplication
        $this->assertEquals(1, preg_match_all("/<\/itunes:duration>/", $content));

    }

    public static function tearDownAfterClass(): void
    {
        chdir('..');
    }
}
