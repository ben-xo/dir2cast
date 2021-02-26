<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class RSS_ItemTest extends TestCase
{
    public function newRSSItem()
    {
        return new RSS_Item();
    }

    public function getDefaultTitle()
    {
        return '(untitled)';
    }

    public function test_rss_item_added_to_podcast_channel()
    {
        $mp = new MyPodcast();

        $item = $this->newRSSItem();
        $item->setTitle('item title');
        $item->setLink('link.mp3');
        $item->setPubDate('today');
        $item->setDescription("<<< &&& >>> ⚠️\netc");
        $item->setLength("1000000");
        $item->setType("test");

        $mp->addRssItem($item);

        $content = $mp->generate();
        $data = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);

        $this->assertEquals('item title', $data->channel->item[0]->title);
        $this->assertEquals('link.mp3', $data->channel->item[0]->link);
        $this->assertEquals('today', $data->channel->item[0]->pubDate);

        // description is a CDATA section, so we have to double-decode it
        $this->assertEquals(
            "<<< &&& >>> ⚠️<br />\netc",
            html_entity_decode((string)$data->channel->item[0]->description)
        );

        $enclosure = $data->channel->item[0]->enclosure;
        $this->assertEquals('link.mp3', $enclosure->attributes()->url);
        $this->assertEquals('1000000', $enclosure->attributes()->length);
        $this->assertEquals('test', $enclosure->attributes()->type);

        // no image by default
        foreach ($data->channel->item[0] as $el) {
            $this->assertNotEquals('image', $el->getName());
        }
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_html_description_with_DESCRIPTION_HTML_set()
    {
        define('DESCRIPTION_HTML', true);
        $mp = new MyPodcast();

        $item = $this->newRSSItem();
        $item->setDescription("<h1>test</h1>");
        $mp->addRssItem($item);

        $content = $mp->generate();
        $data = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);

        // description is a CDATA section, so we have to double-decode it
        $this->assertEquals(
            "<h1>test</h1>",
            (string)$data->channel->item[0]->description
        );
    }

    public function test_rss_item_default_title()
    {
        $mp = new MyPodcast();
        $item = $this->newRSSItem();
        $mp->addRssItem($item);

        $content = $mp->generate();
        $data = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);

        $this->assertEquals($this->getDefaultTitle(), $data->channel->item[0]->title);
    }

    public function test_adds_image_to_item_if_set()
    {
        $mp = new MyPodcast();
        $item = $this->newRSSItem();
        $item->setImage('visuals.jpg'); 
        $mp->addRssItem($item);

        $content = $mp->generate();
        $data = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);

        $this->assertEquals('visuals.jpg', $data->channel->item[0]->image);
    }
}
