<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class MixedMediaExampleTest extends TestCase
{
    public static $file = 'out.xml';
    public static $output = '';
    public static $returncode = 0;

    public static $filemtime = 0;

    public static function setUpBeforeClass(): void
    {
        prepare_testing_dir();

        copy('../fixtures/id3v1_artist_album_title.mp3', '1.mp3');
        copy('../fixtures/id3v2_artist_album_title.mp3', '2.mp3');
        copy('../fixtures/tagged.mp4', '3.mp4');
        copy('../fixtures/id3v2_comment.mp3', '4.mp3');
        copy('../fixtures/id3v2_artist_title_partofaset.mp3', '5.mp3');
        copy('../fixtures/id3v2_artist_title_track.mp3', '6.mp3');

        $now = time();
        touch('1.mp3', $now);
        touch('2.mp3', $now+50);
        touch('3.mp4', $now+100);
        touch('4.mp3', $now+150);
        touch('5.mp3', $now+200);
        touch('6.mp3', $now+250);
        self::$filemtime = $now;

        self::$output = '';
        exec('php dir2cast.php --media-url=https://www.example.com/podcast/ --output=out.xml --min-file-age=0', self::$output, self::$returncode);
    }

    public function test_podcast_creates_output(): void
    {
        $this->assertTrue(file_exists(self::$file));
        $this->assertTrue(strlen(file_get_contents(self::$file)) > 0);
    }

    public function test_podcast_produces_no_warning(): void
    {
        // warns the podcast is empty
        $this->assertSame(
            'Writing RSS to: out.xml',
            implode('\n', self::$output)
        );
        $this->assertSame(0, self::$returncode);
    }

    public function test_podcast_is_valid_with_default_values(): void
    {
        // generated valid XML
        $data = simplexml_load_string(file_get_contents(self::$file));

        $this->assertEquals('testdir', $data->channel->title);
        $this->assertEquals('http://www.example.com/', $data->channel->link);
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
        $this->assertEquals('', $itunes_elements->author);

    }

    public function test_podcast_has_correct_overridable_metadata()
    {
        $data = simplexml_load_string(file_get_contents(self::$file));
        $this->assertEquals('Podcast', $data->channel->description);

        $itunes_elements = $data->channel->children("http://www.itunes.com/dtds/podcast-1.0.dtd");
        $this->assertEquals('Podcast', $itunes_elements->subtitle);
        $this->assertEquals('Podcast', $itunes_elements->summary);

    }

    public function test_itunes_type()
    {
        // generated valid XML
        $data = simplexml_load_string(file_get_contents(self::$file));
        $itdtd = "http://www.itunes.com/dtds/podcast-1.0.dtd";

        // assert itunes:type = serial
        $this->assertEquals('episodic', $data->channel->children($itdtd)->type);
    }    

    public function test_podcast_has_expected_items_with_default_behaviour(): void
    {
        // generated valid XML
        $data = simplexml_load_string(file_get_contents(self::$file));

        $this->assertCount(6, $data->channel->item);
        $this->assertEquals('XXAMPLE#65', $data->channel->item[0]->title);
        $this->assertEquals('EXAMPLE#65', $data->channel->item[1]->title);
        $this->assertEquals('4.mp3',      $data->channel->item[2]->title);
        $this->assertEquals('TTT',        $data->channel->item[3]->title);
        $this->assertEquals('EXAMPLE7',   $data->channel->item[4]->title);
        $this->assertEquals('EXAMPLE3',   $data->channel->item[5]->title);

        $this->assertEquals('https://www.example.com/podcast/6.mp3', $data->channel->item[0]->link);
        $this->assertEquals('https://www.example.com/podcast/5.mp3', $data->channel->item[1]->link);
        $this->assertEquals('https://www.example.com/podcast/4.mp3', $data->channel->item[2]->link);
        $this->assertEquals('https://www.example.com/podcast/3.mp4', $data->channel->item[3]->link);
        $this->assertEquals('https://www.example.com/podcast/2.mp3', $data->channel->item[4]->link);
        $this->assertEquals('https://www.example.com/podcast/1.mp3', $data->channel->item[5]->link);

        $this->assertEquals('',         $data->channel->item[0]->description);
        $this->assertEquals('',         $data->channel->item[1]->description);
        $this->assertEquals('COMMENT8', $data->channel->item[2]->description);
        $this->assertEquals('CCC',      $data->channel->item[3]->description);
        $this->assertEquals('',         $data->channel->item[4]->description);
        $this->assertEquals('',         $data->channel->item[5]->description);

        $this->assertEquals(date('r', self::$filemtime + 250), $data->channel->item[0]->pubDate);
        $this->assertEquals(date('r', self::$filemtime + 200), $data->channel->item[1]->pubDate);
        $this->assertEquals(date('r', self::$filemtime + 150), $data->channel->item[2]->pubDate);
        $this->assertEquals(date('r', self::$filemtime + 100), $data->channel->item[3]->pubDate);
        $this->assertEquals(date('r', self::$filemtime + 50),  $data->channel->item[4]->pubDate);
        $this->assertEquals(date('r', self::$filemtime + 0),   $data->channel->item[5]->pubDate);

        $this->assertEquals($data->channel->item[0]->link, $data->channel->item[0]->enclosure->attributes()->url);
        $this->assertEquals($data->channel->item[1]->link, $data->channel->item[1]->enclosure->attributes()->url);
        $this->assertEquals($data->channel->item[2]->link, $data->channel->item[2]->enclosure->attributes()->url);
        $this->assertEquals($data->channel->item[3]->link, $data->channel->item[3]->enclosure->attributes()->url);
        $this->assertEquals($data->channel->item[4]->link, $data->channel->item[4]->enclosure->attributes()->url);
        $this->assertEquals($data->channel->item[5]->link, $data->channel->item[5]->enclosure->attributes()->url);

        $this->assertEquals((string)filesize('6.mp3'), $data->channel->item[0]->enclosure->attributes()->length);
        $this->assertEquals((string)filesize('5.mp3'), $data->channel->item[1]->enclosure->attributes()->length);
        $this->assertEquals((string)filesize('4.mp3'), $data->channel->item[2]->enclosure->attributes()->length);
        $this->assertEquals((string)filesize('3.mp4'), $data->channel->item[3]->enclosure->attributes()->length);
        $this->assertEquals((string)filesize('2.mp3'), $data->channel->item[4]->enclosure->attributes()->length);
        $this->assertEquals((string)filesize('1.mp3'), $data->channel->item[5]->enclosure->attributes()->length);

        $this->assertEquals('audio/mpeg', $data->channel->item[0]->enclosure->attributes()->type);
        $this->assertEquals('audio/mpeg', $data->channel->item[1]->enclosure->attributes()->type);
        $this->assertEquals('audio/mpeg', $data->channel->item[2]->enclosure->attributes()->type);
        $this->assertEquals('video/mp4',  $data->channel->item[3]->enclosure->attributes()->type);
        $this->assertEquals('audio/mpeg', $data->channel->item[4]->enclosure->attributes()->type);
        $this->assertEquals('audio/mpeg', $data->channel->item[5]->enclosure->attributes()->type);

        //$this->assertEquals('', $data->channel->item[0]->image);
        //$this->assertEquals('', $data->channel->item[1]->image);
        //$this->assertEquals('', $data->channel->item[2]->image);
        //$this->assertEquals('', $data->channel->item[3]->image);
        $this->assertEquals('', $data->channel->item[4]->image);
        $this->assertEquals('', $data->channel->item[5]->image);

        $itdtd = "http://www.itunes.com/dtds/podcast-1.0.dtd";

        $this->assertEquals('',         $data->channel->item[0]->children($itdtd)->summary);
        $this->assertEquals('',         $data->channel->item[1]->children($itdtd)->summary);
        $this->assertEquals('COMMENT8', $data->channel->item[2]->children($itdtd)->summary);
        $this->assertEquals('CCC',      $data->channel->item[3]->children($itdtd)->summary);
        $this->assertEquals('',         $data->channel->item[4]->children($itdtd)->summary);
        //$this->assertEquals('',         $data->channel->item[5]->children($itdtd)->summary);

        $this->assertEquals('XRTIST#65', $data->channel->item[0]->children($itdtd)->author);
        $this->assertEquals('ARTIST#65', $data->channel->item[1]->children($itdtd)->author);
        $this->assertEquals('',          $data->channel->item[2]->children($itdtd)->author);
        $this->assertEquals('AAA',       $data->channel->item[3]->children($itdtd)->author);
        $this->assertEquals('ARTIST7',   $data->channel->item[4]->children($itdtd)->author);
        $this->assertEquals('ARTIST3',   $data->channel->item[5]->children($itdtd)->author);

        $this->assertEquals('XRTIST#65', $data->channel->item[0]->children($itdtd)->subtitle);
        $this->assertEquals('ARTIST#65', $data->channel->item[1]->children($itdtd)->subtitle);
        $this->assertEquals('',          $data->channel->item[2]->children($itdtd)->subtitle);
        $this->assertEquals('AAA',       $data->channel->item[3]->children($itdtd)->subtitle);
        //$this->assertEquals('ARTIST7', $data->channel->item[4]->children($itdtd)->subtitle);
        $this->assertEquals('ARTIST3',   $data->channel->item[5]->children($itdtd)->subtitle);
    }

    public function test_itunes_episode()
    {
        // generated valid XML
        $data = simplexml_load_string(file_get_contents(self::$file));
        $itdtd = "http://www.itunes.com/dtds/podcast-1.0.dtd";
        // $this->assertEquals('1', $data->channel->item[0]->children($itdtd)->episode);
        $this->assertEmpty($data->channel->item[1]->children($itdtd)->episode);
        $this->assertEmpty($data->channel->item[2]->children($itdtd)->episode);
        $this->assertEmpty($data->channel->item[3]->children($itdtd)->episode);
        $this->assertEmpty($data->channel->item[4]->children($itdtd)->episode);
        $this->assertEmpty($data->channel->item[5]->children($itdtd)->episode);
    }

    public function test_itunes_season()
    {
        // generated valid XML
        $data = simplexml_load_string(file_get_contents(self::$file));
        $itdtd = "http://www.itunes.com/dtds/podcast-1.0.dtd";
        $this->assertEmpty($data->channel->item[0]->children($itdtd)->season);
        // $this->assertEquals('Season 1', $data->channel->item[1]->children($itdtd)->season);
        $this->assertEmpty($data->channel->item[2]->children($itdtd)->season);
        $this->assertEmpty($data->channel->item[3]->children($itdtd)->season);
        $this->assertEmpty($data->channel->item[4]->children($itdtd)->season);
        $this->assertEmpty($data->channel->item[5]->children($itdtd)->season);
    }

    public function test_podcast_has_expected_overrideable_fields()
    {
        $data = simplexml_load_string(file_get_contents(self::$file));

        $this->assertEquals('', $data->channel->item[2]->image);
        $this->assertEquals('', $data->channel->item[3]->image);

        $itdtd = "http://www.itunes.com/dtds/podcast-1.0.dtd";

        $this->assertEquals('', $data->channel->item[5]->children($itdtd)->summary);
        $this->assertEquals('ARTIST7', $data->channel->item[4]->children($itdtd)->subtitle);
        $this->assertEquals('', $data->channel->item[3]->children($itdtd)->image);
        $this->assertEquals('', $data->channel->item[2]->children($itdtd)->image);
        
    }


    public static function tearDownAfterClass(): void
    {
        chdir('..');
    }

}
