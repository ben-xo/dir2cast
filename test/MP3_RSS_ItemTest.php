<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class MP3_RSS_ItemTest extends Media_RSS_ItemTest
{
    public function getDefaultType()
    {
        return 'audio/mpeg';
    }

    protected $media_rss_item_class = 'MP3_RSS_Item';
}
