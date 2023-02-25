<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class Media_RSS_Item_id3v2_artist_title_partofsetTest extends Media_RSS_ItemTest
{
    public function getMediaFileContent()
    {
        return file_get_contents('fixtures/id3v2_artist_title_partofaset.mp3');
    }

    public function getMediaFileLength()
    {
        return filesize('fixtures/id3v2_artist_title_partofaset.mp3');
    }

    public function getID3Artist()
    {
        return 'ARTIST#65';
    }

    public function getID3Title()
    {
        return 'EXAMPLE#65';
    }

    public function getID3PartOfASet()
    {
        return 'Season 1';
    }

    public function getDefaultTitle()
    {
        return 'EXAMPLE#65';
    }

    public function getDefaultSubtitle()
    {
        return 'ARTIST#65';
    }
}
