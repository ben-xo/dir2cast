<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class Media_RSS_Item_id3v1_title_LONG_TITLESTest extends Media_RSS_Item_id3v1_titleTest
{
    public static function setUpBeforeClass(): void
    {
        Media_RSS_Item_id3v1_titleTest::setUpBeforeClass();
        Media_RSS_Item::$LONG_TITLES = true;
    }

    public function getDefaultTitle()
    {
        return 'EXAMPLE1';
    }

    public function getDefaultSubtitle()
    {
        return '';
    }
}
