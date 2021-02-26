<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class Dir_PodcastTest extends PodcastTest
{

    public static function setUpBeforeClass(): void
    {
        PodcastTest::setUpBeforeClass();
        prepare_testing_dir();
    }

    public function setUp(): void
    {
        $this->delete_test_files();
        parent::setUp();
        Dir_Podcast::$RECURSIVE_DIRECTORY_ITERATOR = false;
        Dir_Podcast::$ITEM_COUNT = 10;
    }

    public function newPodcast()
    {
        return new Dir_Podcast('.');
    }

    public function createTestItems()
    {
        file_put_contents('test1.mp3', 'content');
        file_put_contents('test2.mp4', 'content');
        file_put_contents('test3.m4a', 'content');
        file_put_contents('test4.other', 'content');

        $filemtime = time();
        touch('test1.mp3', $filemtime+50);
        touch('test2.mp4', $filemtime);
        touch('test3.m4a', $filemtime-50);
        touch('test4.other', $filemtime-100);

        return $filemtime;
    }

    public function createEmptyTestItems()
    {
        file_put_contents('test1.mp3', '');
        file_put_contents('test2.mp4', '');
        file_put_contents('test3.m4a', '');
        file_put_contents('test4.other', '');

        $filemtime = time();
        touch('test1.mp3', $filemtime+50);
        touch('test2.mp4', $filemtime);
        touch('test3.m4a', $filemtime-50);
        touch('test4.other', $filemtime-100);

        return $filemtime;
    }

    public function test_empty_dir_leads_to_empty_podcast()
    {
        $mp = $this->newPodcast();
        $content = $mp->generate();
        $this->assertEmpty($mp->getItems());
        $this->assertEquals(0, $mp->getMaxMtime());
    }

    public function test_three_supported_files_of_zero_length_not_added_to_podcast()
    {
        $filemtime = $this->createEmptyTestItems();
        
        $mp = $this->newPodcast();
        $content = $mp->generate();
        $this->assertEmpty($mp->getItems());
        $this->assertEquals(0, $mp->getMaxMtime());
    }

    public function test_three_supported_files_added_to_podcast()
    {
        $filemtime = $this->createTestItems();
        $mp = $this->newPodcast();
        $content = $mp->generate();
        $this->assertCount(3, $mp->getItems());

        $items = $mp->getItems();
        $this->assertInstanceOf(MP3_RSS_Item::class, $items[0]);
        $this->assertInstanceOf(MP4_RSS_Item::class, $items[1]);
        $this->assertInstanceOf(M4A_RSS_Item::class, $items[2]);
        $this->assertEquals($filemtime+50, $mp->getMaxMtime());
    }

    public function test_generating_twice_doesnt_rescan()
    {
        $filemtime = $this->createTestItems();
        $mp = $this->newPodcast();
        $mp->generate();

        // delete all the files
        $this->delete_test_files();

        $content = $mp->generate(); // generate again

        $this->assertCount(3, $mp->getItems());

        $items = $mp->getItems();
        $this->assertInstanceOf(MP3_RSS_Item::class, $items[0]);
        $this->assertInstanceOf(MP4_RSS_Item::class, $items[1]);
        $this->assertInstanceOf(M4A_RSS_Item::class, $items[2]);
        $this->assertEquals($filemtime+50, $mp->getMaxMtime());
    }

    public function test_helpers_added_to_found_items()
    {
        $filemtime = $this->createTestItems();
        $mp = $this->newPodcast();

        $helper = $this->createMock(Podcast_Helper::class);
        $helper->expects($this->exactly(3))->method('appendToItem');

        $helper2 = $this->createMock(Podcast_Helper::class);
        $helper2->expects($this->exactly(3))->method('appendToItem');

        $mp->addHelper($helper);
        $mp->addHelper($helper2);

        $content = $mp->generate();
    }

    public function test_files_added_to_podcast_obeys_ITEM_COUNT()
    {
        Dir_Podcast::$ITEM_COUNT = 2;

        $filemtime = $this->createTestItems();

        $mp = $this->newPodcast();

        $helper = $this->createMock(Podcast_Helper::class);
        $helper->expects($this->exactly(2))->method('appendToItem');

        $helper2 = $this->createMock(Podcast_Helper::class);
        $helper2->expects($this->exactly(2))->method('appendToItem');

        $mp->addHelper($helper);
        $mp->addHelper($helper2);

        $content = $mp->generate();
        $this->assertCount(2, $mp->getItems());

        $items = $mp->getItems();
        $this->assertInstanceOf(MP3_RSS_Item::class, $items[0]);
        $this->assertInstanceOf(MP4_RSS_Item::class, $items[1]);
    }

    protected function delete_test_files()
    {
        file_exists('test1.mp3') && unlink('test1.mp3');
        file_exists('test2.mp4') && unlink('test2.mp4');
        file_exists('test3.m4a') && unlink('test3.m4a');
        file_exists('test4.other') && unlink('test4.other');
    }

    public function tearDown(): void
    {
        $this->delete_test_files();
        parent::tearDown();
    }

    public static function tearDownAfterClass(): void
    {
        PodcastTest::tearDownAfterClass();
        chdir('..');
    }
}
