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

    // test image png
    public function test_png_image_from_filesystem() {
        touch('example.png');
        $item = $this->newRSSItem();
        $this->assertEquals('http://www.example.com/mp3/example.png', $item->getImage());
        unlink('example.png');
    }

    // test image png no extension
    public function test_png_image_from_filesystem_no_extension() {
        touch('example.png');
        $item = new RSS_File_Item('example');
        $this->assertEquals('http://www.example.com/mp3/example.png', $item->getImage());
        unlink('example.png');
    }

    // test image png dot only
    public function test_png_image_from_filesystem_dot_only() {
        touch('example.png');
        $item = new RSS_File_Item('example.');
        $this->assertEquals('http://www.example.com/mp3/example.png', $item->getImage());
        unlink('example.png');
    }

    // test image jpg
    public function test_jpg_image_from_filesystem() {
        touch('example.jpg');
        $item = $this->newRSSItem();
        $this->assertEquals('http://www.example.com/mp3/example.jpg', $item->getImage());
        unlink('example.jpg');
    }

    // test image jpg no extension
    public function test_jpg_image_from_filesystem_no_extension() {
        touch('example.jpg');
        $item = new RSS_File_Item('example');
        $this->assertEquals('http://www.example.com/mp3/example.jpg', $item->getImage());
        unlink('example.jpg');
    }

    // test image jpg dot only
    public function test_jpg_image_from_filesystem_dot_only() {
        touch('example.jpg');
        $item = new RSS_File_Item('example.');
        $this->assertEquals('http://www.example.com/mp3/example.jpg', $item->getImage());
        unlink('example.jpg');
    }

    public function tearDown(): void
    {
        file_exists('example.jpg') && unlink('example.jpg');
        file_exists('example.png') && unlink('example.png');
    }

    // TODO: implement these in RSS_File_Item_iTunes_Podcast_HelperTest

    // test summary override
    // test summary with matching filename
    // test summary with matching filename with no extension


    // test subtitle override
    // test subtitle with matching filename
    // test subtitle with matching filename with no extension

}
