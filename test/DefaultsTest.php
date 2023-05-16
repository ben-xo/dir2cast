<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DefaultsTest extends TestCase
{
    public static $file = 'out.xml';
    public static $output = '';
    public static $returncode = 0;

    public static $content = '';

    public static function setUpBeforeClass(): void
    {
        prepare_testing_dir();
        exec('php dir2cast.php --output=out.xml', self::$output, self::$returncode);
    }

    public function test_default_dir2cast_ini_is_functionally_empty(): void
    {
        $ini_content = file_get_contents('./dir2cast.ini');
        $this->assertGreaterThan(10000, strlen($ini_content), "ini file is unusually small. If you have edited it, some tests will fail.");
        $ini_content = preg_replace("/^(;.*|\s*)\r\n/m", '', $ini_content);
        $this->assertEquals("", $ini_content, "ini file is not the default. Some tests may fail.");
    }

    public function test_default_empty_podcast_creates_output(): void
    {
        $this->assertTrue(file_exists(self::$file));

        self::$content = file_get_contents(self::$file);
        $this->assertTrue(strlen(self::$content) > 0);
    }

    public function test_default_empty_podcast_produces_warning(): void
    {
        // warns the podcast is empty
        $this->assertSame(
            'Writing RSS to: out.xml\n** Warning: generated podcast found no episodes.',
            implode('\n', self::$output)
        );
        $this->assertSame(255, self::$returncode);
    }

    public function test_default_empty_podcast_is_valid_with_default_values(): void
    {
        // generated valid XML
        $data = simplexml_load_string(self::$content);

        $this->assertEquals('testdir', $data->channel->title);
        $this->assertEquals('http://www.example.com/', $data->channel->link);
        $this->assertEquals('Podcast', $data->channel->description);
        $this->assertEquals('en-us', $data->channel->language);
        $this->assertEquals('60', $data->channel->ttl);

        $this->assertEquals(date("Y"), $data->channel->copyright);
        $this->assertGreaterThan(time() - 100, strtotime((string)$data->channel->lastBuildDate));
        $this->assertLessThan(time() + 100, strtotime((string)$data->channel->lastBuildDate));
        $this->assertEquals(1, preg_match(
            "#^dir2cast \d+.\d+ by Ben XO \(https://github\.com/ben-xo/dir2cast/\)$#",
            (string)$data->channel->generator
        ));

        $atom_elements = $data->channel->children("http://www.w3.org/2005/Atom");
        $this->assertEquals('http://www.example.com/rss', $atom_elements->link->attributes()['href']);
        $this->assertEquals('self', $atom_elements->link->attributes()['rel']);
        $this->assertEquals('application/rss+xml', $atom_elements->link->attributes()['type']);

        $itunes_elements = $data->channel->children("http://www.itunes.com/dtds/podcast-1.0.dtd");
        $this->assertEquals('Podcast', $itunes_elements->subtitle);
        $this->assertEquals('Podcast', $itunes_elements->summary);
        $this->assertEquals('', $itunes_elements->author);

    }

    public static function tearDownAfterClass(): void
    {
        chdir('..');
    }

}
