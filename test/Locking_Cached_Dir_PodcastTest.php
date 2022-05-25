<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class Locking_Cached_Dir_PodcastTest extends Cached_Dir_PodcastTest
{

    public static function setUpBeforeClass(): void
    {
        Cached_Dir_PodcastTest::setUpBeforeClass();
    }

    public function newPodcast($offset=0)
    {
        $podcast = new Locking_Cached_Dir_Podcast('.', './temp');
        $podcast->setClockOffset($offset);
        $podcast->init();
        return $podcast;
    }


    public function test_cache_file_is_locked()
    {
        $filemtime = $this->createTestItems();
        $mp = $this->newPodcast();
        $content = $mp->generate();
        unset($mp);

        foreach(glob(temp_xml_glob()) as $cachefile)
        {
            $fh = fopen($cachefile, 'a');
            $this->assertTrue(flock($fh, LOCK_NB | LOCK_EX));
            fclose($fh);
        }

        $mp2 = $this->newPodcast();
        $content2 = $mp2->generate();

        foreach(glob(temp_xml_glob()) as $cachefile)
        {
            $fh = fopen($cachefile, 'a');
            $this->assertFalse(flock($fh, LOCK_NB | LOCK_EX));
            fclose($fh);
        }
    }

    public function test_temporary_lockfile_is_deleted_if_not_used()
    {
        $this->assertEmpty(glob('temp' . DIRECTORY_SEPARATOR . '*'));
        $mp = $this->newPodcast();
        $this->assertNotEmpty(glob('temp' . DIRECTORY_SEPARATOR . '*'));
        unset($mp);
        $this->assertEmpty(glob('temp' . DIRECTORY_SEPARATOR . '*'));
    }

    public static function tearDownAfterClass(): void
    {
        Cached_Dir_PodcastTest::tearDownAfterClass();
    }
}
