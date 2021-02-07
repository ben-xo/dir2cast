<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class MyPodcast extends Podcast
{

}

/**
 * @runInSeparateProcess
 * @preserveGlobalState disabled
 */
final class PodcastTest extends TestCase
{

    public static function setUpBeforeClass(): void
    {
    }

    public function test_generate_with_defaults_is_valid_xml()
    {
        $mp = new MyPodcast();
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
        $mp = new MyPodcast();
        $content = $mp->generate();
        $data = simplexml_load_string($content);
        
        $this->assertGreaterThan(time() - 100, strtotime((string)$data->channel->lastBuildDate));
        $this->assertLessThan(time() + 100, strtotime((string)$data->channel->lastBuildDate));
    }

    // public function test_basic_feed_properties()
    // {
    // }

    // public function test_dynamic_copyright_year()
    // {
    // }

    // public function test_feed_image()
    // {
    // }

    // public function test_html_entities_are_rss_compatible()
    // {
    // }

    // public function test_added_items()
    // {

    // }

    // public function test_helpers_applied_to_already_added_items()
    // {

    // }

    // public function test_helpers_applied_to_newly_added_items()
    // {

    // }

    // public function test_helpers_namespaces_applied_to_document()
    // {
    // }

    // public function test_helpers_content_added_to_channel()
    // {

    // }
}
