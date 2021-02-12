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

    // test summary default
    public function test_summary_default() {
        $item = $this->newRSSItem();
        $this->assertEquals('', $item->getSummary());
    }

    // test summary override
    public function test_summary_override() {
        file_put_contents('example.txt', 'WRONG');
        $item = $this->newRSSItem();
        $item->setSummary('RIGHT');
        $this->assertEquals('RIGHT', $item->getSummary());
        unlink('example.txt');
    }

    // test summary
    public function test_summary_from_filesystem() {
        file_put_contents('example.txt', 'special summary!');
        $item = $this->newRSSItem();
        $this->assertEquals('special summary!', $item->getSummary());
        unlink('example.txt');
    }

    // test summary no extension
    public function test_summary_from_filesystem_no_extension() {
        file_put_contents('example.txt', 'special summary 2!');
        $item = new RSS_File_Item('example');
        $this->assertEquals('special summary 2!', $item->getSummary());
        unlink('example.txt');
    }

    // test summary dot only
    public function test_summary_from_filesystem_dot_only() {
        file_put_contents('example.txt', 'special summary 3!');
        $item = new RSS_File_Item('example.');
        $this->assertEquals('special summary 3!', $item->getSummary());
        unlink('example.txt');
    }

    // test subtitle default
    public function test_subtitle_default() {
        $item = $this->newRSSItem();
        $this->assertEquals('', $item->getSubtitle());
    }

    // test subtitle override
    public function test_subtitle_override() {
        file_put_contents('example_subtitle.txt', 'WRONG');
        $item = $this->newRSSItem();
        $item->setSubtitle('RIGHT');
        $this->assertEquals('RIGHT', $item->getSubtitle());
        unlink('example_subtitle.txt');
    }

    // test subtitle
    public function test_subtitle_from_filesystem() {
        file_put_contents('example_subtitle.txt', 'special subtitle!');
        $item = $this->newRSSItem();
        $this->assertEquals('special subtitle!', $item->getSubtitle());
        unlink('example_subtitle.txt');
    }

    // test subtitle no extension
    public function test_subtitle_from_filesystem_no_extension() {
        file_put_contents('example_subtitle.txt', 'special subtitle 2!');
        $item = new RSS_File_Item('example');
        $this->assertEquals('special subtitle 2!', $item->getSubtitle());
        unlink('example_subtitle.txt');
    }

    // test subtitle dot only
    public function test_subtitle_from_filesystem_dot_only() {
        file_put_contents('example_subtitle.txt', 'special subtitle 3!');
        $item = new RSS_File_Item('example.');
        $this->assertEquals('special subtitle 3!', $item->getSubtitle());
        unlink('example_subtitle.txt');
    }

    public function tearDown(): void
    {
        file_exists('example.jpg') && unlink('example.jpg');
        file_exists('example.png') && unlink('example.png');
        file_exists('example.txt') && unlink('example.txt');
        file_exists('example_subtitle.txt') && unlink('example_subtitle.txt');
    }

    // TODO: implement these in RSS_File_Item_iTunes_Podcast_HelperTest

    // test summary override
    // test summary with matching filename
    // test summary with matching filename with no extension


    // test subtitle override
    // test subtitle with matching filename
    // test subtitle with matching filename with no extension

}
