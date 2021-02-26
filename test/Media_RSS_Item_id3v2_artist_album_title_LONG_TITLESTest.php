<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class Media_RSS_Item_id3v2_artist_album_title_LONG_TITLESTest extends Media_RSS_Item_id3v2_artist_album_titleTest
{
    public static function setUpBeforeClass(): void
    {
        Media_RSS_Item_id3v2_artist_album_titleTest::setUpBeforeClass();
        Media_RSS_Item::$LONG_TITLES = true;
    }

    public function getDefaultTitle()
    {
        return 'ALBUM7 - ARTIST7 - EXAMPLE7';
    }

    public function getDefaultSubtitle()
    {
        return '';
    }
}
