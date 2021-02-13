<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class Media_RSS_Item_SerializationTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        defined('MP3_URL') || define('MP3_URL', 'http://www.example.com/mp3/');
        defined('MP3_DIR') || define('MP3_DIR', getcwd());
    }

    public function setUp(): void
    {
        file_put_contents('example.mp3', '');
    }

    public function test_serialize_and_deserialize_yield_the_same_thing()
    {
        $item = new Media_RSS_Item('example.mp3');
        $item->setA('a');
        $item->setB('b');

        $serialized = serialize($item);
        $item2 = unserialize($serialized);

        $this->assertEquals('a', $item2->getA());
        $this->assertEquals('b', $item2->getB());
    }

    public function tearDown(): void
    {
        unlink('example.mp3');
    }
}
