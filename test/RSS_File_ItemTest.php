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

    public function test_constructor_sets_default_properties_from_filename()
    {
        $item = $this->newRSSItem();
        $this->assertEquals('example.mp3', $item->getFilename());
        $this->assertEquals('example.mp3', $item->getTitle());
        $this->assertEquals('http://www.example.com/mp3/example.mp3', $item->getLink());
        $this->assertEquals('application/octet-stream', $item->getType());
    }

    public function test_filename_with_full_path()
    {
        $item = new RSS_File_Item(getcwd() . '/example.mp3');
        $this->assertEquals(getcwd() . '/example.mp3', $item->getFilename());
        $this->assertEquals('example.mp3', $item->getTitle());
        $this->assertEquals('http://www.example.com/mp3/example.mp3', $item->getLink());
    }

    public function test_filename_without_extension()
    {
        $item = new RSS_File_Item('example');
        $this->assertEquals('example', $item->getFilename());
        $this->assertEquals('example', $item->getTitle());
        $this->assertEquals('http://www.example.com/mp3/example', $item->getLink());
    }

    public function test_filename_with_full_path_without_extension()
    {
        $item = new RSS_File_Item(getcwd() . '/example');
        $this->assertEquals(getcwd() . '/example', $item->getFilename());
        $this->assertEquals('example', $item->getTitle());
        $this->assertEquals('http://www.example.com/mp3/example', $item->getLink());
    }

    // test summary override
    // test summary with matching filename
    // test summary with matching filename with no extension


    // test subtitle override
    // test subtitle with matching filename
    // test subtitle with matching filename with no extension

    // test image png
    // test image jpg
}
