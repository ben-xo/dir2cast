<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class Dir_PodcastTest extends PodcastTest
{

    public static function setUpBeforeClass(): void
    {
        PodcastTest::setUpBeforeClass();
        prepare_testing_dir();
        Dir_Podcast::$RECURSIVE_DIRECTORY_ITERATOR = false;
    }

    public static function tearDownAfterClass(): void
    {
        PodcastTest::tearDownAfterClass();
        chdir('..');
        // rmrf('./testdir');
    }

    public function newPodcast()
    {
        return new Dir_Podcast('.');
    }
}
