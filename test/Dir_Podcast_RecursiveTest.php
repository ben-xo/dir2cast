<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class Dir_Podcast_RecursiveTest extends Dir_PodcastTest
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
        mkdir('test5');
        file_put_contents('test1/test1.mp3', 'content');
        file_put_contents('test2/test2.mp4', 'content');
        file_put_contents('test3/test3.m4a', 'content');
        file_put_contents('test4/test4.other', 'content');
        file_put_contents('test5/test5.m4b', 'content');

        $filemtime = time();
        touch('test1/test1.mp3', $filemtime+50);
        touch('test2/test2.mp4', $filemtime);
        touch('test3/test3.m4a', $filemtime-50);
        touch('test4/test4.other', $filemtime-100);
        touch('test5/test5.m4b', $filemtime-75);

        return $filemtime;
    }

    public function createEmptyTestItems()
    {
        mkdir('test1');
        mkdir('test2');
        mkdir('test3');
        mkdir('test4');
        mkdir('test5');
        file_put_contents('test1/test1.mp3', '');
        file_put_contents('test2/test2.mp4', '');
        file_put_contents('test3/test3.m4a', '');
        file_put_contents('test4/test4.other', '');
        file_put_contents('test5/test5.m4b', '');

        $filemtime = time();
        touch('test1/test1.mp3', $filemtime+50);
        touch('test2/test2.mp4', $filemtime);
        touch('test3/test3.m4a', $filemtime-50);
        touch('test4/test4.other', $filemtime-100);
        touch('test5/test5.m4b', $filemtime-75);

        return $filemtime;
    }

    public function test_empty_dir_leads_to_empty_podcast()
    {
        $mp = $this->newPodcast();
        $content = $mp->generate();
        $this->assertEmpty($mp->getItems());
    }

    protected function delete_test_files()
    {
        file_exists('test1/test1.mp3') && unlink('test1/test1.mp3');
        file_exists('test2/test2.mp4') && unlink('test2/test2.mp4');
        file_exists('test3/test3.m4a') && unlink('test3/test3.m4a');
        file_exists('test4/test4.other') && unlink('test4/test4.other');
        file_exists('test5/test5.m4b') && unlink('test5/test5.m4b');
        is_dir('test1') && rmdir('test1');
        is_dir('test2') && rmdir('test2');
        is_dir('test3') && rmdir('test3');
        is_dir('test4') && rmdir('test4');
        is_dir('test5') && rmdir('test5');
        parent::delete_test_files();
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

        file_put_contents('test2/test2.txt', 'party123');
        touch('test2/test2.txt', $now);

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

    public function tearDown(): void
    {
        file_exists('test2/test2.txt') && unlink('test2/test2.txt');
        parent::tearDown();
    }

    public static function tearDownAfterClass(): void
    {
        Dir_PodcastTest::tearDownAfterClass();
    }
}
