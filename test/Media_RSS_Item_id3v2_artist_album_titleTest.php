<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class Media_RSS_Item_id3v2_artist_album_titleTest extends Media_RSS_ItemTest
{
    public function getMediaFileContent()
    {
        return file_get_contents('fixtures/id3v2_artist_album_title.mp3');
    }

    public function getMediaFileLength()
    {
        return filesize('fixtures/id3v2_artist_album_title.mp3');
    }

    public function getID3Artist()
    {
        return 'ARTIST7';
    }

    public function getID3Album()
    {
        return 'ALBUM7';
    }

    public function getID3Title()
    {
        return 'EXAMPLE7';
    }

    public function getDefaultTitle()
    {
        return 'EXAMPLE7';
    } 

    public function getDefaultSubtitle()
    {
        return 'ARTIST7';
    }
}
