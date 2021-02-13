<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class Media_RSS_Item_id3v1_artist_album_title_LONG_TITLESTest extends Media_RSS_Item_id3v1_artist_album_titleTest
{
    public static function setUpBeforeClass(): void
    {
        Media_RSS_Item_id3v1_artist_album_titleTest::setUpBeforeClass();
        Media_RSS_Item::$LONG_TITLES = true;
    }

    public function getDefaultTitle()
    {
        return 'ALBUM3 - ARTIST3 - EXAMPLE3';
    }

    public function getDefaultSubtitle()
    {
        return '';
    }
}
