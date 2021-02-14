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
        $mp = $this->newPodcast();
        $this->assertEmpty(glob('temp/*'));
        $content = $mp->generate();
        $this->assertNotEmpty(glob('temp/*'));
    }

    public function test_uses_generated_cache_file_if_min_time_not_elapsed_yet()
    {
        $filemtime = $this->createTestItems();
        $mp = $this->newPodcast();
        $mp->init();
        $content = $mp->generate();

        // this should be ignored
        file_put_contents('extra.mp3', 'new data');
        touch('extra.mp3', $filemtime + 200);

        $mp2 = $this->newPodcast();
        $mp2->init();
        $content2 = $mp->generate();

        // should not pick up extra.mp3 as the cache file isn't old enough
        $this->assertEquals($content, $content2);
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
