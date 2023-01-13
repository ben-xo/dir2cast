<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class ITunesPodcastSeasonTest extends MixedMediaExampleTest
{
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
        MixedMediaExampleTest::$filemtime = $now;

        file_put_contents('./dir2cast.ini', "ITUNES_TYPE = serial\n");

        MixedMediaExampleTest::$output = '';
        exec('php dir2cast.php --media-url=https://www.example.com/podcast/ --output=out.xml --min-file-age=0', MixedMediaExampleTest::$output, MixedMediaExampleTest::$returncode);
    }

    public function test_itunes_type()
    {
        // generated valid XML
        $data = simplexml_load_string(file_get_contents(self::$file));
        $itdtd = "http://www.itunes.com/dtds/podcast-1.0.dtd";

        // assert itunes:type = serial
        $this->assertEquals('serial', $data->channel->children($itdtd)->type);
    }

    public function test_itunes_season()
    {
        // generated valid XML
        $data = simplexml_load_string(file_get_contents(self::$file));
        $itdtd = "http://www.itunes.com/dtds/podcast-1.0.dtd";
        $this->assertEmpty($data->channel->item[0]->children($itdtd)->season);
        $this->assertEquals('Season 1', $data->channel->item[1]->children($itdtd)->season);
        $this->assertEmpty($data->channel->item[2]->children($itdtd)->season);
        $this->assertEmpty($data->channel->item[3]->children($itdtd)->season);
        $this->assertEmpty($data->channel->item[4]->children($itdtd)->season);
        $this->assertEmpty($data->channel->item[5]->children($itdtd)->season);
    }

    public function test_itunes_episode()
    {
        // generated valid XML
        $data = simplexml_load_string(file_get_contents(self::$file));
        $itdtd = "http://www.itunes.com/dtds/podcast-1.0.dtd";
        $this->assertEquals('1', $data->channel->item[0]->children($itdtd)->episode);
        $this->assertEmpty($data->channel->item[1]->children($itdtd)->episode);
        $this->assertEmpty($data->channel->item[2]->children($itdtd)->episode);
        $this->assertEmpty($data->channel->item[3]->children($itdtd)->episode);
        $this->assertEmpty($data->channel->item[4]->children($itdtd)->episode);
        $this->assertEmpty($data->channel->item[5]->children($itdtd)->episode);
    }


}
