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
        Dir_Podcast::$DEBUG = false;
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
        file_put_contents('test5.m4b', 'content');

        $filemtime = time();
        touch('test1.mp3', $filemtime+50);
        touch('test2.mp4', $filemtime);
        touch('test3.m4a', $filemtime-50);
        touch('test4.other', $filemtime-100);
        touch('test5.m4b', $filemtime-75);

        return $filemtime;
    }

    public function createEmptyTestItems()
    {
        file_put_contents('test1.mp3', '');
        file_put_contents('test2.mp4', '');
        file_put_contents('test3.m4a', '');
        file_put_contents('test4.other', '');
        file_put_contents('test5.m4b', '');

        $filemtime = time();
        touch('test1.mp3', $filemtime+50);
        touch('test2.mp4', $filemtime);
        touch('test3.m4a', $filemtime-50);
        touch('test4.other', $filemtime-100);
        touch('test5.m4b', $filemtime-75);

        return $filemtime;
    }

    public function test_empty_dir_leads_to_empty_podcast()
    {
        $mp = $this->newPodcast();
        $content = $mp->generate();
        $this->assertEmpty($mp->getItems());
        $this->assertEquals(0, $mp->getMaxMtime());
    }

    public function test_four_supported_files_of_zero_length_not_added_to_podcast()
    {
        $filemtime = $this->createEmptyTestItems();
        
        $mp = $this->newPodcast();
        $content = $mp->generate();
        $this->assertEmpty($mp->getItems());
        $this->assertEquals(0, $mp->getMaxMtime());
    }

    public function test_four_supported_files_added_to_podcast()
    {
        $filemtime = $this->createTestItems();
        $mp = $this->newPodcast();
        $content = $mp->generate();
        $this->assertCount(4, $mp->getItems());

        $items = $mp->getItems();
        $this->assertInstanceOf(MP3_RSS_Item::class, $items[0]);
        $this->assertInstanceOf(MP4_RSS_Item::class, $items[1]);
        $this->assertInstanceOf(M4A_RSS_Item::class, $items[2]);
        $this->assertInstanceOf(M4A_RSS_Item::class, $items[3]);
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

        $this->assertCount(4, $mp->getItems());

        $items = $mp->getItems();
        $this->assertInstanceOf(MP3_RSS_Item::class, $items[0]);
        $this->assertInstanceOf(MP4_RSS_Item::class, $items[1]);
        $this->assertInstanceOf(M4A_RSS_Item::class, $items[2]);
        $this->assertInstanceOf(M4A_RSS_Item::class, $items[3]);
        $this->assertEquals($filemtime+50, $mp->getMaxMtime());
    }

    public function test_regenerates_if_metadata_files_added()
    {
        Media_RSS_Item::$DESCRIPTION_SOURCE = 'summary';
        $filemtime = $this->createTestItems();
        age_dir_by('.', 200);

        $mp = $this->newPodcast();
        $before = $mp->generate();

        $items = $mp->getItems();
        $this->assertInstanceOf(MP3_RSS_Item::class, $items[0]);
        $this->assertInstanceOf(MP4_RSS_Item::class, $items[1]);
        $this->assertInstanceOf(M4A_RSS_Item::class, $items[2]);
        $this->assertInstanceOf(M4A_RSS_Item::class, $items[3]);
        $this->assertEquals($filemtime+50 - 200, $mp->getMaxMtime());

        unset($mp); // releases locks

        $this->assertEquals(0, preg_match('/party123/', $before));

        $now = time();
        age_dir_by('.', 500); // whizz past min cache time

        file_put_contents('test2.txt', 'party123');
        touch('test2.txt', $now);

        age_dir_by('.', 500); // whizz past min file age

        $mp = $this->newPodcast();
        $after = $mp->generate(); // generate again

        $items = $mp->getItems();
        // ordering should be preserved
        $this->assertInstanceOf(MP3_RSS_Item::class, $items[0]);
        $this->assertInstanceOf(MP4_RSS_Item::class, $items[1]);
        $this->assertInstanceOf(M4A_RSS_Item::class, $items[2]);
        $this->assertInstanceOf(M4A_RSS_Item::class, $items[3]);
        $this->assertEquals($now-500, $mp->getMaxMtime());
        $this->assertEquals('party123', $items[1]->getSummary());

        $this->assertEquals(1, preg_match('/party123/', $after));
        unset($mp);
    }

    public function test_helpers_added_to_found_items()
    {
        $filemtime = $this->createTestItems();
        $mp = $this->newPodcast();

        $helper = $this->createMock(Podcast_Helper::class);
        $helper->expects($this->atLeastOnce())->method('id')->willReturn('Mock1');
        $helper->expects($this->exactly(4))->method('appendToItem');

        $helper2 = $this->createMock(Podcast_Helper::class);
        $helper2->expects($this->atLeastOnce())->method('id')->willReturn('Mock2');
        $helper2->expects($this->exactly(4))->method('appendToItem');

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
        $helper->expects($this->atLeastOnce())->method('id')->willReturn('Mock1');
        $helper->expects($this->exactly(2))->method('appendToItem');

        $helper2 = $this->createMock(Podcast_Helper::class);
        $helper2->expects($this->atLeastOnce())->method('id')->willReturn('Mock2');
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
        file_exists('test2.txt') && unlink('test2.txt');
        file_exists('test5.m4b') && unlink('test5.m4b');
    }

    public function tearDown(): void
    {
        Media_RSS_Item::$DESCRIPTION_SOURCE = 'comment';
        $this->delete_test_files();
        parent::tearDown();
    }

    public static function tearDownAfterClass(): void
    {
        PodcastTest::tearDownAfterClass();
        chdir('..');
    }
}
