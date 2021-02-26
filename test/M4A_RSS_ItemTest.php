<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class M4A_RSS_ItemTest extends Media_RSS_ItemTest
{
    public function getDefaultType()
    {
        return 'audio/mp4';
    }

    protected $media_rss_item_class = 'M4A_RSS_Item';
}
