<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class Cached_Dir_PodcastTest extends Dir_PodcastTest
{

    public static function setUpBeforeClass(): void
    {
        Dir_PodcastTest::setUpBeforeClass();
        Cached_Dir_Podcast::$MIN_CACHE_TIME = 5;
        Cached_Dir_Podcast::$DEBUG = false;
    }

    public function setUp(): void
    {
        parent::setUp();
        mkdir('temp');
    }

    public function newPodcast($offset=0)
    {
        $podcast = new Cached_Dir_Podcast('.', './temp');
        $podcast->setClockOffset($offset);
        $podcast->init();
        return $podcast;
    }

    public function test_generate_saves_a_cache_file()
    {
        $this->createTestItems();
        age_dir_by('.', 3600);

        $this->assertEmpty(glob('temp' . DIRECTORY_SEPARATOR . '*'));
        $mp = $this->newPodcast();
        $content = $mp->generate();
        $this->assertNotEmpty(glob('temp' . DIRECTORY_SEPARATOR . '*'));
    }

    public function test_uses_generated_cache_file_if_min_time_not_elapsed_yet()
    {
        $this->createTestItems();
        age_dir_by('.', 3600);

        $mp = $this->newPodcast();
        $content = $mp->generate();
        unset($mp); // release lock, in sub tests

        age_dir_by('.', 2);

        // this should be ignored
        file_put_contents('extra.mp3', 'new data');

        $mp2 = $this->newPodcast(2);
        $content2 = $mp2->generate();

        // should not pick up extra.mp3 as the cache file isn't old enough
        $this->assertEquals($content, $content2);
        $this->assertEquals(0, preg_match('/extra\.mp3/', $content2));
    }

    public function test_does_not_use_generated_cache_file_if_min_time_has_elapsed_and_theres_new_content()
    {
        $this->createTestItems();
        age_dir_by('.', 3600);

        $mp = $this->newPodcast();
        $content = $mp->generate();
        unset($mp); // release lock, in sub tests

        age_dir_by('.', 10);

        // this should be considered
        file_put_contents('extra.mp3', 'new data');

        $mp2 = $this->newPodcast(10);
        $content2 = $mp2->generate();

        // should pick up extra.mp3 as the cache file is older than the min, and there's new content
        $this->assertNotEquals($content, $content2);
        $this->assertEquals(1, preg_match('/extra\.mp3/', $content2));
    }

    public function test_does_not_use_generated_cache_file_if_min_time_has_elapsed_and_theres_additional_old_content()
    {
        $this->createTestItems();
        age_dir_by('.', 3600);

        $mp = $this->newPodcast();
        $content = $mp->generate();
        unset($mp); // release lock, in sub tests

        age_dir_by('.', 10);

        // this should be considered
        file_put_contents('extra.mp3', 'new data');
        touch('extra.mp3', time() - 86400);

        $mp2 = $this->newPodcast(10);
        $content2 = $mp2->generate();

        // should pick up extra.mp3 as the cache file is older than the min, and there's new content
        $this->assertNotEquals($content, $content2);
        $this->assertEquals(1, preg_match('/extra\.mp3/', $content2));
    }

    public function test_renews_cache_if_old_but_not_stale()
    {
        $this->createTestItems();
        age_dir_by('.', 3600);

        $mp = $this->newPodcast();
        $content = $mp->generate();
        unset($mp); // release lock, in sub tests

        age_dir_by('.', 3600);

        $mp2 = $this->newPodcast(3600);
        $content2 = $mp2->generate();

        // should have used cache file anyway
        $this->assertEquals($content, $content2);

        clearstatcache();
        foreach(glob(temp_xml_glob()) as $filename)
        {
            // cache file should have been refreshed
            $this->assertGreaterThan(time() - 3, filemtime($filename));
        }
    }

    public function test_lastBuildDate_is_valid_whether_served_from_cache_or_not()
    {
        $this->createTestItems();
        age_dir_by('.', 3600);

        $mp = $this->newPodcast();

        $this->assertFalse($mp->isCached());

        $lastBuildDate = date('r');
        $mp->generate();

        clearstatcache();
        $this->assertTrue($mp->isCached());

        $this->assertEquals($lastBuildDate, $mp->getLastBuildDate());
        unset($mp); // release lock, in sub tests

        age_dir_by('.', 3600);

        $mp2 = $this->newPodcast(3600);
        $this->assertTrue($mp2->isCached());
        $mp2->generate();
        clearstatcache();
        $this->assertTrue($mp2->isCached());

        $this->assertEquals($lastBuildDate, $mp2->getLastBuildDate());
        unset($mp2);

        clearstatcache();
        age_dir_by('.', 3600);
        sleep(1); // not much choice here!
        $mp3 = $this->newPodcast(3600+3600);
        $mp3->generate();

        clearstatcache();
        $this->assertEquals($lastBuildDate, $mp3->getLastBuildDate());
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
