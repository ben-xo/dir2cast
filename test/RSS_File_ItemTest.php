<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class RSS_File_ItemTest extends RSS_ItemTest
{
    public static function setUpBeforeClass(): void
    {
        define('MP3_URL', 'http://www.example.com/mp3/');
        define('MP3_DIR', getcwd());
    }

    public function newRSSItem()
    {
        return new RSS_File_Item('example.mp3');
    }

    public function getDefaultTitle()
    {
        return 'example.mp3';
    }

}
