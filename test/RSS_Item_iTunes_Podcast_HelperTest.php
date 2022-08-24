<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class RSS_Item_iTunes_Podcast_HelperTest extends TestCase
{

    public static function setUpBeforeClass(): void
    {
    }

    public function test_rss_item_added_to_podcast_channel_has_itunes_properties()
    {
        $mp = new MyPodcast();
        $helper = new iTunes_Podcast_Helper();
        $mp->addHelper($helper);

        $item = new RSS_Item();
        $item->setSummary("whaddya wanna know?");
        $item->setSubtitle("");
        $item->setDuration('1:23');
        $item->setID3Artist('Ben XO');
        $item->setImage('cover.jpg');

        $mp->addRssItem($item);

        $content = $mp->generate();
        $data = simplexml_load_string($content, 'SimpleXMLElement');

        $item = $data->channel->item[0];
        $itunes_item = $item->children('http://www.itunes.com/dtds/podcast-1.0.dtd');

        $this->assertEquals('1:23', $itunes_item->duration);
        $this->assertEquals('Ben XO', $itunes_item->author);
        $this->assertEquals('whaddya wanna know?', $itunes_item->summary);
        $this->assertEquals('cover.jpg', $itunes_item->image->attributes()->href);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_rss_item_itunes_subtitle_suffix()
    {
        iTunes_Podcast_Helper::$ITUNES_SUBTITLE_SUFFIX = ' Click here for more…';

        $mp = new MyPodcast();
        $helper = new iTunes_Podcast_Helper();
        $mp->addHelper($helper);

        $item0 = new RSS_Item();
        $item0->setSubtitle("testing");

        $item1 = new RSS_Item();

        $mp->addRssItem($item0);
        $mp->addRssItem($item1);

        $content = $mp->generate();
        $data = simplexml_load_string($content, 'SimpleXMLElement');
        $item = $data->channel->item;
        $itunes_item0 = $item[0]->children('http://www.itunes.com/dtds/podcast-1.0.dtd');
        $itunes_item1 = $item[1]->children('http://www.itunes.com/dtds/podcast-1.0.dtd');

        $this->assertEquals('testing Click here for more…', $itunes_item0->subtitle);
        $this->assertEquals(' Click here for more…', $itunes_item1->subtitle);
    }


}
