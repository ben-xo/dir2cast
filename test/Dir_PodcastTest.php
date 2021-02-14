<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class Dir_PodcastTest extends PodcastTest
{

    public static function setUpBeforeClass(): void
    {
        PodcastTest::setUpBeforeClass();
        prepare_testing_dir();
        Dir_Podcast::$RECURSIVE_DIRECTORY_ITERATOR = false;
        Dir_Podcast::$ITEM_COUNT = 10;
    }

    public function newPodcast()
    {
        return new Dir_Podcast('.');
    }

    public function test_empty_dir_leads_to_empty_podcast()
    {
        $mp = $this->newPodcast();
        $content = $mp->generate();
        $this->assertEmpty($mp->getItems());
    }

    public function test_three_supported_files_of_zero_length_not_added_to_podcast()
    {
        $filemtime = time();
        touch('test1.mp3', $filemtime);
        touch('test2.mp4', $filemtime-50);
        touch('test3.m4a', $filemtime-100);
        touch('test4.other', $filemtime-150);
        
        $mp = $this->newPodcast();
        $content = $mp->generate();
        $this->assertEmpty($mp->getItems());
    }

    public function test_three_supported_files_added_to_podcast()
    {
        file_put_contents('test1.mp3', 'content');
        file_put_contents('test2.mp4', 'content');
        file_put_contents('test3.m4a', 'content');
        file_put_contents('test4.other', 'content');

        $filemtime = time();
        touch('test1.mp3', $filemtime);
        touch('test2.mp4', $filemtime-50);
        touch('test3.m4a', $filemtime-100);
        touch('test4.other', $filemtime-150);

        $mp = $this->newPodcast();
        $content = $mp->generate();
        $this->assertCount(3, $mp->getItems());

        $items = $mp->getItems();
        $this->assertInstanceOf(MP3_RSS_Item::class, $items[0]);
        $this->assertInstanceOf(MP4_RSS_Item::class, $items[1]);
        $this->assertInstanceOf(M4A_RSS_Item::class, $items[2]);
    }

    public function tearDown(): void
    {
        file_exists('test1.mp3') && unlink('test1.mp3');
        file_exists('test2.mp4') && unlink('test2.mp4');
        file_exists('test3.m4a') && unlink('test3.m4a');
        file_exists('test4.other') && unlink('test4.other');
        parent::tearDown();
    }

    public static function tearDownAfterClass(): void
    {
        PodcastTest::tearDownAfterClass();
        chdir('..');
        // rmrf('./testdir');
    }
}
