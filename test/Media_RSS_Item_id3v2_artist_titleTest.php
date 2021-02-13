<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class Media_RSS_Item_id3v2_artist_titleTest extends Media_RSS_ItemTest
{
    public function getMediaFileContent()
    {
        return file_get_contents('fixtures/id3v2_artist_title.mp3');
    }

    public function getMediaFileLength()
    {
        return filesize('fixtures/id3v2_artist_title.mp3');
    }

    public function getID3Artist()
    {
        return 'ARTIST6';
    }

    public function getID3Title()
    {
        return 'EXAMPLE6';
    }

    public function getDefaultTitle()
    {
        return 'EXAMPLE6';
    }

    public function getDefaultSubtitle()
    {
        return 'ARTIST6';
    }
}
