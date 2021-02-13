<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class Media_RSS_Item_id3v2_artist_title_LONG_TITLESTest extends Media_RSS_Item_id3v2_artist_titleTest
{
    public static function setUpBeforeClass(): void
    {
        Media_RSS_Item_id3v2_artist_titleTest::setUpBeforeClass();
        Media_RSS_Item::$LONG_TITLES = true;
    }

    public function getDefaultTitle()
    {
        return 'ARTIST6 - EXAMPLE6';
    }

    public function getDefaultSubtitle()
    {
        return '';
    }
}
