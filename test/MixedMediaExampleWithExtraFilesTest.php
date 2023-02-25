<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class MixedMediaExampleWithExtraFilesTest extends MixedMediaExampleTest
{
    public static $file = 'out.xml';
 
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
        MixedMediaExampleTest::$filemtime = $now;

        file_put_contents('description.txt', 'Best Podcast Ever!');
        file_put_contents('itunes_subtitle.txt', 'Best Podcast on iTunes Ever!');
        file_put_contents('itunes_summary.txt', 'Four of the best example episodes');

        copy('../fixtures/empty.jpg', 'image.jpg');
        copy('../fixtures/empty.png', 'itunes_image.png');

        copy('../fixtures/empty.jpg', '3.jpg');
        copy('../fixtures/empty.png', '4.png');

        file_put_contents('1.txt', 'New, Improved Summary');
        file_put_contents('2_subtitle.txt', 'Return Of The Episode');

        MixedMediaExampleTest::$output = '';
        exec('php dir2cast.php --media-url=https://www.example.com/podcast/ --output=out.xml --min-file-age=0',
            MixedMediaExampleTest::$output,
            MixedMediaExampleTest::$returncode
        );
    }

    public function test_podcast_has_correct_overridable_metadata()
    {
        $data = simplexml_load_string(file_get_contents(self::$file));
        $this->assertEquals('Best Podcast Ever!', $data->channel->description);

        $this->assertEquals('https://www.example.com/podcast/image.jpg', $data->channel->image->url);
        $this->assertEquals('http://www.example.com/', $data->channel->image->link);
        $this->assertEquals('testdir', $data->channel->image->title);

        $itunes_elements = $data->channel->children("http://www.itunes.com/dtds/podcast-1.0.dtd");
        $this->assertEquals('Best Podcast on iTunes Ever!', $itunes_elements->subtitle);
        $this->assertEquals('Four of the best example episodes', $itunes_elements->summary);
        $this->assertEquals('https://www.example.com/podcast/itunes_image.png', $itunes_elements->image->attributes()->href);
    }

    public function test_podcast_has_expected_overrideable_fields()
    {
        $data = simplexml_load_string(file_get_contents(self::$file));

        $this->assertEquals('https://www.example.com/podcast/4.png', $data->channel->item[2]->image);
        $this->assertEquals('https://www.example.com/podcast/3.jpg', $data->channel->item[3]->image);

        $itdtd = "http://www.itunes.com/dtds/podcast-1.0.dtd";

        $this->assertEquals('New, Improved Summary', $data->channel->item[5]->children($itdtd)->summary);
        $this->assertEquals('Return Of The Episode', $data->channel->item[4]->children($itdtd)->subtitle);
        $this->assertEquals('https://www.example.com/podcast/4.png', $data->channel->item[2]->children($itdtd)->image->attributes()->href);
        $this->assertEquals('https://www.example.com/podcast/3.jpg', $data->channel->item[3]->children($itdtd)->image->attributes()->href);
        
    }

    public static function tearDownAfterClass(): void
    {
        chdir('..');
    }

}
