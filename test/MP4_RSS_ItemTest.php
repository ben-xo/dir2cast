<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class MP4_RSS_ItemTest extends Media_RSS_ItemTest
{
    public function getDefaultType()
    {
        return 'video/mp4';
    }

    protected $media_rss_item_class = 'MP4_RSS_Item';
}
