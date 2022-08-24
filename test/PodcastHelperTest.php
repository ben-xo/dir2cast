<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PodcastHelperTest extends TestCase
{

    public static function setUpBeforeClass(): void
    {

    }

    public function test_helpers_applied_to_newly_added_items()
    {
        $helper = $this->createMock(Podcast_Helper::class);
        $helper->expects($this->atLeastOnce())->method('id')->willReturn('Mock1');
        $helper->expects($this->exactly(2))->method('appendToItem');

        $helper2 = $this->createMock(Podcast_Helper::class);
        $helper2->expects($this->atLeastOnce())->method('id')->willReturn('Mock2');
        $helper2->expects($this->exactly(2))->method('appendToItem');

        $mp = new MyPodcast();
        $mp->addHelper($helper);
        $mp->addHelper($helper2);

        $item = new RSS_Item();
        $mp->addRssItem($item);

        $item2 = new RSS_Item();
        $mp->addRssItem($item2);

        $mp->generate();
    }

    public function test_helpers_applied_to_already_added_items()
    {
        $helper = $this->createMock(Podcast_Helper::class);
        $helper->expects($this->atLeastOnce())->method('id')->willReturn('Mock1');
        $helper->expects($this->exactly(2))->method('appendToItem');

        $helper2 = $this->createMock(Podcast_Helper::class);
        $helper2->expects($this->atLeastOnce())->method('id')->willReturn('Mock2');
        $helper2->expects($this->exactly(2))->method('appendToItem');

        $mp = new MyPodcast();

        $item = new RSS_Item();
        $mp->addRssItem($item);

        $item2 = new RSS_Item();
        $mp->addRssItem($item2);

        $mp->addHelper($helper);
        $mp->addHelper($helper2);

        $mp->generate();

    }

    public function test_helpers_given_opportunity_to_add_namespace()
    {
        $helper = $this->createMock(Podcast_Helper::class);
        $helper->expects($this->atLeastOnce())->method('id')->willReturn('Mock1');
        $helper->expects($this->once())->method('addNamespaceTo');

        $helper2 = $this->createMock(Podcast_Helper::class);
        $helper2->expects($this->atLeastOnce())->method('id')->willReturn('Mock2');
        $helper2->expects($this->once())->method('addNamespaceTo');

        $mp = new MyPodcast();
        $mp->addHelper($helper);
        $mp->addHelper($helper2);

        $content = $mp->generate();
    }

    // TODO: test appendImage
    // TODO: test pre_generate
    // TODO: test post_generate
}
