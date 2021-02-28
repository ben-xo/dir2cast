<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class iTunes_Podcast_HelperTest extends TestCase
{

    public function test_adds_namespace_to_podcast()
    {
        $mp = new MyPodcast();
        $mp->addHelper(new iTunes_Podcast_Helper());
        $content = $mp->generate();

        $this->assertEquals(1, preg_match(
            '#<rss[^>]* xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"[^>]*>#', 
            $content
        ));
    }

    public function assertChannelHasElement($expected_element, $expected_value, $channel)
    {
        $children = $channel->children('http://www.itunes.com/dtds/podcast-1.0.dtd');
        $this->assertEquals($expected_value, $children->$expected_element);
    }

    public function assertChannelHasElementWithAttribute($expected_element, $expected_attr_name, $expected_value, $channel)
    {
        $children = $channel->children('http://www.itunes.com/dtds/podcast-1.0.dtd');
        $this->assertEquals(
            $expected_value, 
            $children->$expected_element->attributes()->$expected_attr_name
        );
    }

    public function assertChannelHasElementWithAttributeAtIndex($expected_element, $expected_attr_name, $expected_value, $expected_index, $channel)
    {
        $children = $channel->children('http://www.itunes.com/dtds/podcast-1.0.dtd');
        $this->assertEquals(
            $expected_value, 
            $children->$expected_element[$expected_index]->attributes()->$expected_attr_name
        );
    }

    public function test_adds_arbitrary_set_params_to_podcast()
    {
        $mp = new MyPodcast();
        $itunes = $mp->addHelper(new iTunes_Podcast_Helper());
        $itunes->setA('First');
        $itunes->setB('Second');
        $content = $mp->generate();

        $data = simplexml_load_string($content, 'SimpleXMLElement');
        $this->assertChannelHasElement('a', 'First', $data->channel);
        $this->assertChannelHasElement('b', 'Second', $data->channel);
    }

    public function test_adds_simple_category_to_podcast()
    {
        $mp = new MyPodcast();
        $itunes = $mp->addHelper(new iTunes_Podcast_Helper());
        $itunes->addCategories('Music');
        $content = $mp->generate();

        $data = simplexml_load_string($content, 'SimpleXMLElement');
        $this->assertChannelHasElementWithAttribute('category', 'text', 'Music', $data->channel);
    }

    public function test_adds_multiple_categories_to_podcast()
    {
        $mp = new MyPodcast();
        $itunes = $mp->addHelper(new iTunes_Podcast_Helper());
        $itunes->addCategories('Music, Documentary');
        $content = $mp->generate();

        $data = simplexml_load_string($content, 'SimpleXMLElement');
        $this->assertChannelHasElementWithAttributeAtIndex('category', 'text', 'Music', 0, $data->channel);
        $this->assertChannelHasElementWithAttributeAtIndex('category', 'text', 'Documentary', 1, $data->channel);
    }

    public function test_adds_multiple_subcategories_to_podcast()
    {
        $mp = new MyPodcast();
        $itunes = $mp->addHelper(new iTunes_Podcast_Helper());
        $itunes->addCategories('Music > Techno, Documentary > Music & Film');
        $content = $mp->generate();

        $data = simplexml_load_string($content, 'SimpleXMLElement');
        $this->assertChannelHasElementWithAttributeAtIndex('category', 'text', 'Music', 0, $data->channel);
        $this->assertChannelHasElementWithAttributeAtIndex('category', 'text', 'Documentary', 1, $data->channel);

        $category = $data->channel->children('http://www.itunes.com/dtds/podcast-1.0.dtd')->category;
        $this->assertEquals('Techno', $category[0]->category[0]->attributes()->text);
        $this->assertEquals('Music & Film', $category[1]->category[0]->attributes()->text);
    }

    public function test_adds_owner_name_to_podcast()
    {
        $mp = new MyPodcast();
        $itunes = $mp->addHelper(new iTunes_Podcast_Helper());
        $itunes->setOwnerName('Ben XO');
        $content = $mp->generate();

        $data = simplexml_load_string($content, 'SimpleXMLElement');
        $owner = $data->channel->children('http://www.itunes.com/dtds/podcast-1.0.dtd')->owner;
        $this->assertEquals('Ben XO', $owner->name);
    }


    public function test_adds_owner_email_to_podcast()
    {
        $mp = new MyPodcast();
        $itunes = $mp->addHelper(new iTunes_Podcast_Helper());
        $itunes->setOwnerEmail('example@example.com');
        $content = $mp->generate();

        $data = simplexml_load_string($content, 'SimpleXMLElement');
        $owner = $data->channel->children('http://www.itunes.com/dtds/podcast-1.0.dtd')->owner;
        $this->assertEquals('example@example.com', $owner->email);
    }

    public function test_adds_both_owner_name_and_email_to_podcast()
    {
        $mp = new MyPodcast();
        $itunes = $mp->addHelper(new iTunes_Podcast_Helper());
        $itunes->setOwnerName('Ben XO');
        $itunes->setOwnerEmail('example@example.com');
        $content = $mp->generate();

        $data = simplexml_load_string($content, 'SimpleXMLElement');
        $owner = $data->channel->children('http://www.itunes.com/dtds/podcast-1.0.dtd')->owner;
        $this->assertEquals('Ben XO', $owner->name);
        $this->assertEquals('example@example.com', $owner->email);
    }

    public function test_adds_explicit_to_podcast()
    {
        $mp = new MyPodcast();
        $itunes = $mp->addHelper(new iTunes_Podcast_Helper());
        $itunes->setExplicit('yes');
        $content = $mp->generate();

        $data = simplexml_load_string($content, 'SimpleXMLElement');
        $this->assertChannelHasElement('explicit', 'yes', $data->channel);
    }

    public function test_adds_image_to_podcast()
    {
        $mp = new MyPodcast();
        $itunes = $mp->addHelper(new iTunes_Podcast_Helper());
        $itunes->setImage('itunes_image.jpg');
        $content = $mp->generate();

        $data = simplexml_load_string($content, 'SimpleXMLElement');
        $this->assertChannelHasElementWithAttribute('image', 'href', 'itunes_image.jpg', $data->channel);

    }
}
