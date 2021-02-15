<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class Cached_Dir_PodcastTest extends Dir_PodcastTest
{

    public static function setUpBeforeClass(): void
    {
        Dir_PodcastTest::setUpBeforeClass();
        Cached_Dir_Podcast::$MIN_CACHE_TIME = 5;
    }

    public function setUp(): void
    {
        parent::setUp();
        mkdir('temp');
    }

    public function newPodcast()
    {
        $podcast = new Cached_Dir_Podcast('.', './temp');
        $podcast->init();
        return $podcast;
    }

    public function test_generate_saves_a_cache_file()
    {
        $this->createTestItems();
        $this->assertEmpty(glob('temp/*'));
        $mp = $this->newPodcast();
        $content = $mp->generate();
        $this->assertNotEmpty(glob('temp/*'));
    }

    public function test_uses_generated_cache_file_if_min_time_not_elapsed_yet()
    {
        $filemtime = $this->createTestItems();
        $mp = $this->newPodcast();
        $content = $mp->generate();
        unset($mp); // release lock, in sub tests

        // this should be ignored
        file_put_contents('extra.mp3', 'new data');
        touch('extra.mp3', $filemtime + 200);

        $mp2 = $this->newPodcast();
        $content2 = $mp2->generate();

        // should not pick up extra.mp3 as the cache file isn't old enough
        $this->assertEquals($content, $content2);
    }

    public function test_does_not_use_generated_cache_file_if_min_time_has_elapsed_and_theres_new_content()
    {
        Cached_Dir_Podcast::$MIN_CACHE_TIME = -1;
        $filemtime = $this->createTestItems();
        $mp = $this->newPodcast();
        $content = $mp->generate();
        unset($mp); // release lock, in sub tests

        // this should be considered
        file_put_contents('extra.mp3', 'new data');
        touch('extra.mp3', $filemtime + 200);

        $mp2 = $this->newPodcast();
        $content2 = $mp2->generate();

        // should not pick up extra.mp3 as the cache file isn't old enough
        $this->assertNotEquals($content, $content2);
    }


    public function test_renews_cache_if_old_but_not_stale()
    {
        $filemtime = $this->createTestItems();
        touch('test1.mp3', $filemtime - 10); // older than min time
        $mp = $this->newPodcast();
        $content = $mp->generate();
        unset($mp); // release lock, in sub tests

        // cache file now exists. Artificially age it by 6 seconds so it's older than MIN_CACHE_TIME
        foreach(glob('temp/*.xml') as $filename)
        {
            touch($filename, $filemtime - 6);
        }

        $mp2 = $this->newPodcast();
        $content2 = $mp2->generate();

        // should have used cache file anyway
        $this->assertEquals($content, $content2);

        foreach(glob('temp/*.xml') as $filename)
        {
            // cache file should have been refreshed
            $this->assertGreaterThan($filemtime - 6, filemtime($filename));
        }
    }

    public function tearDown(): void
    {
        file_exists('extra.mp3') && unlink('extra.mp3');
        is_dir('temp') && rmrf('temp');
        parent::tearDown();
    }

    public static function tearDownAfterClass(): void
    {
        Dir_PodcastTest::tearDownAfterClass();
    }
}
