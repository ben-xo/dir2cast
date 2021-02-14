<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class Dir_PodcastTest_Recursive extends Dir_PodcastTest
{

    public static function setUpBeforeClass(): void
    {
        Dir_PodcastTest::setUpBeforeClass();
    }

    public function setUp(): void
    {
        parent::setUp();
        Dir_Podcast::$RECURSIVE_DIRECTORY_ITERATOR = true;
    }

    public function createTestItems()
    {
        mkdir('test1');
        mkdir('test2');
        mkdir('test3');
        mkdir('test4');
        file_put_contents('test1/test1.mp3', 'content');
        file_put_contents('test2/test2.mp4', 'content');
        file_put_contents('test3/test3.m4a', 'content');
        file_put_contents('test4/test4.other', 'content');

        $filemtime = time();
        touch('test1/test1.mp3', $filemtime);
        touch('test2/test2.mp4', $filemtime-50);
        touch('test3/test3.m4a', $filemtime-100);
        touch('test4/test4.other', $filemtime-150);

        return $filemtime;
    }

    public function test_empty_dir_leads_to_empty_podcast()
    {
        $mp = $this->newPodcast();
        $content = $mp->generate();
        $this->assertEmpty($mp->getItems());
    }

    public function test_three_supported_files_of_zero_length_not_added_to_podcast()
    {
        mkdir('test1');
        mkdir('test2');
        mkdir('test3');
        mkdir('test4');

        $filemtime = time();
        touch('test1/test1.mp3', $filemtime);
        touch('test2/test2.mp4', $filemtime-50);
        touch('test3/test3.m4a', $filemtime-100);
        touch('test4/test4.other', $filemtime-150);
        
        $mp = $this->newPodcast();
        $content = $mp->generate();
        $this->assertEmpty($mp->getItems());
    }

    public function tearDown(): void
    {
        file_exists('test1/test1.mp3') && unlink('test1/test1.mp3');
        file_exists('test2/test2.mp4') && unlink('test2/test2.mp4');
        file_exists('test3/test3.m4a') && unlink('test3/test3.m4a');
        file_exists('test4/test4.other') && unlink('test4/test4.other');
        is_dir('test1') && rmdir('test1');
        is_dir('test2') && rmdir('test2');
        is_dir('test3') && rmdir('test3');
        is_dir('test4') && rmdir('test4');
        parent::tearDown();
    }

    public static function tearDownAfterClass(): void
    {
        Dir_PodcastTest::tearDownAfterClass();
        // rmrf('./testdir');
    }
}
