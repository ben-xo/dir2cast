<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class PodcastTest extends TestCase
{

    public static function setUpBeforeClass(): void
    {
    }

    public function newPodcast()
    {
        return new MyPodcast();
    }

    public function test_generate_with_defaults_is_valid_xml()
    {
        $mp = $this->newPodcast();
        $content = $mp->generate();
        $data = simplexml_load_string($content);

        $this->assertEquals('2.0', $data->attributes()->version);

        $this->assertNotNull($data->channel->title);
        $this->assertNotNull($data->channel->link);
        $this->assertNotNull($data->channel->description);
        $this->assertNotNull($data->channel->language);
        $this->assertNotNull($data->channel->ttl);
        $this->assertNotNull($data->channel->webMaster);
        $this->assertNotNull($data->channel->copyright);
        $this->assertNotNull($data->channel->lastBuildDate);
        $this->assertNotNull($data->channel->generator);
    }

    public function test_generate_with_defaults_has_current_build_date()
    {
        $mp = $this->newPodcast();
        $content = $mp->generate();
        $data = simplexml_load_string($content);
        
        $this->assertGreaterThan(time() - 100, strtotime((string)$data->channel->lastBuildDate));
        $this->assertLessThan(time() + 100, strtotime((string)$data->channel->lastBuildDate));
    }

    public function test_basic_feed_properties()
    {
        $mp = $this->newPodcast();

        $mp->setTitle('the title');
        $mp->setLink('http://www.example.com/');
        $mp->setDescription('You may be eaten by a grue');
        $mp->setLanguage('en-gb');
        $mp->setTtl('100');
        $mp->setWebMaster('Ben XO');
        $mp->setCopyright('1984');
        $mp->setGenerator('The Unit Test Factory');

        $content = $mp->generate();
        $data = simplexml_load_string($content);

        $this->assertEquals('the title', $data->channel->title);
        $this->assertEquals('http://www.example.com/', $data->channel->link);
        $this->assertEquals('You may be eaten by a grue', $data->channel->description);
        $this->assertEquals('en-gb', $data->channel->language);
        $this->assertEquals('100', $data->channel->ttl);
        $this->assertEquals('Ben XO', $data->channel->webMaster);
        $this->assertEquals('1984', $data->channel->copyright);
        $this->assertEquals('The Unit Test Factory', $data->channel->generator);
    }

    public function test_dynamic_copyright_year()
    {
        $mp = $this->newPodcast();
        $mp->setCopyright('Copyright Ben XO %YEAR%');
        $content = $mp->generate();
        $data = simplexml_load_string($content);
        $this->assertEquals('Copyright Ben XO ' . date('Y'), $data->channel->copyright);
    }

    public function test_feed_no_image()
    {
        $mp = $this->newPodcast();
        $content = $mp->generate();
        $data = simplexml_load_string($content);
        foreach ($data->channel->children() as $el) {
            $this->assertNotEquals('image', $el->getName());
        }
    }

    public function test_feed_with_image()
    {
        $mp = $this->newPodcast();
        $mp->setImage('image.jpg');
        $mp->setLink('http://www.example.com/');
        $mp->setTitle('Something');
        $content = $mp->generate();
        $data = simplexml_load_string($content);
        $this->assertNotNull($data->channel->image);
        $this->assertEquals('image.jpg', $data->channel->image->url);
        $this->assertEquals('http://www.example.com/', $data->channel->image->link);
        $this->assertEquals('Something', $data->channel->image->title);
    }


    public function test_html_entities_are_rss_compatible()
    {
        $mp = $this->newPodcast();

        $mp->setTitle("<\x06<\x07<\x08");
        $mp->setDescription('⛄️');
        $mp->setWebMaster('>>>');
        $mp->setGenerator('&amp;');

        $content = $mp->generate();
        $data = simplexml_load_string($content);

        $this->assertEquals('<<<', $data->channel->title);
        $this->assertEquals('⛄️', $data->channel->description);
        $this->assertEquals('>>>', $data->channel->webMaster);
        $this->assertEquals('&amp;', $data->channel->generator);

        $this->assertEquals(0, preg_match('/&amp;/', $content));
        $this->assertEquals(0, preg_match('/&lt;/', $content));
        $this->assertEquals(0, preg_match('/&gt;/', $content));
    }

}
