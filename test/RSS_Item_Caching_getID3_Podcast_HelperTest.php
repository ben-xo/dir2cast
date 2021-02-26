<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class RSS_Item_Caching_getID3_Podcast_HelperTest extends RSS_Item_getID3_Podcast_HelperTest
{

    public static function setUpBeforeClass(): void
    {
        RSS_Item_getID3_Podcast_HelperTest::setUpBeforeClass();
    }

    public function setUp(): void
    {
        parent::setUp();
        mkdir('temp');
    }

    public function newHelper()
    {
        return new Caching_getID3_Podcast_Helper('temp', new getID3_Podcast_Helper());
    }

    public function tearDown(): void
    {
        parent::tearDown();
        rmrf('temp');
    }

    public static function tearDownAfterClass(): void
    {
        RSS_Item_getID3_Podcast_HelperTest::tearDownAfterClass();
    }
}
