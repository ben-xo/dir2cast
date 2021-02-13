<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class Media_RSS_Item_id3v1_artist_title_LONG_TITLESTest extends Media_RSS_Item_id3v1_artist_titleTest
{
    public static function setUpBeforeClass(): void
    {
        Media_RSS_Item_id3v1_artist_titleTest::setUpBeforeClass();
        Media_RSS_Item::$LONG_TITLES = true;
    }

    public function getDefaultTitle()
    {
        return 'ARTIST2 - EXAMPLE2';
    }

    public function getDefaultSubtitle()
    {
        return '';
    }
}
