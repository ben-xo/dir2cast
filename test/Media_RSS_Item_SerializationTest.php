<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class Media_RSS_Item_SerializationTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        RSS_File_Item::$FILES_URL = 'http://www.example.com/mp3/';
        RSS_File_Item::$FILES_DIR = getcwd();
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

    public function test_unserialize_rejects_invalid_serialization_version()
    {
        $item = new Media_RSS_Item('example.mp3');
        $item->setA('a');
        $item->setB('b');

        $serialized = serialize($item);
        $serialized = preg_replace('/:"serialVersion";i:\d+;/', ':"serialVersion";i:0;', $serialized);
        $this->expectException(SerializationException::class);
        $item2 = unserialize($serialized);
    }

    /**
     * TODO: I am using serialize/unserialize wrong here. I should unset these properties on load, not save.
     *       But that would require a version bump in the cache constant.
     */
    public function test_unserialize_does_not_overwrite_properties_set_from_fs_metadata()
    {
        $item = new Media_RSS_Item('example.mp3');
        $item->setA('a');
        $item->setB('b');

        $serialized = $item->serialize($item);

        file_put_contents('example2.abc', 'abcde');
        $filemtime = time()-100;
        touch('example2.abc', $filemtime);
        $item2 = new Media_RSS_Item('example2.abc');
        $item2->unserialize($serialized);

        $this->assertEquals('a', $item2->getA());
        $this->assertEquals('b', $item2->getB());

        $this->assertEquals(5, $item2->getLength());
        $this->assertEquals(date('r', $filemtime), $item2->getPubDate());
        $this->assertEquals('example2.abc', $item2->getFilename());
        $this->assertEquals('abc', $item2->getExtension());
        $this->assertEquals('http://www.example.com/mp3/example2.abc', $item2->getLink());
    }

    public function tearDown(): void
    {
        unlink('example.mp3');
        file_exists('example2.abc') && unlink('example2.abc');
    }
}
