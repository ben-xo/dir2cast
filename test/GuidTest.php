<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Tests for the optional <guid> element (GUID_SOURCE).
 *
 * The date-based GUID is designed for archives that reuse the same filenames
 * every week: the filename stays the same, but the media file's date makes the
 * GUID unique per week. It must also stay stable when an episode is moved to
 * another folder (e.g. a 'previous' archive), because only the basename and the
 * media mtime are used.
 */
final class GuidTest extends TestCase
{
    /** @var string */
    private static $root;

    /** @var string */
    private $old_files_url;

    /** @var string */
    private $old_files_dir;

    /** @var string */
    private $old_guid_source;

    public static function setUpBeforeClass(): void
    {
        self::$root = sys_get_temp_dir() . '/dir2cast-guid-' . getmypid() . '-' . mt_rand();
        mkdir(self::$root, 0777, true);
    }

    public static function tearDownAfterClass(): void
    {
        self::removeDir(self::$root);
    }

    public function setUp(): void
    {
        $this->old_files_url = RSS_File_Item::$FILES_URL;
        $this->old_files_dir = RSS_File_Item::$FILES_DIR;
        $this->old_guid_source = RSS_File_Item::$GUID_SOURCE;

        RSS_File_Item::$FILES_URL = 'http://www.example.com/mp3/';
        RSS_File_Item::$FILES_DIR = self::$root;
        RSS_File_Item::$GUID_SOURCE = 'none';
    }

    public function tearDown(): void
    {
        RSS_File_Item::$FILES_URL = $this->old_files_url;
        RSS_File_Item::$FILES_DIR = $this->old_files_dir;
        RSS_File_Item::$GUID_SOURCE = $this->old_guid_source;
    }

    public function test_no_guid_by_default(): void
    {
        $item = $this->makeItem('live/episode.m4a', mktime(12, 2, 1, 11, 25, 2026));

        $this->assertSame('', $item->getGuid());
        $this->assertFalse(isset($this->generateItem($item)->guid));
    }

    public function test_url_guid_is_the_enclosure_url_and_a_permalink(): void
    {
        RSS_File_Item::$GUID_SOURCE = 'url';

        $item = $this->makeItem('live/episode.m4a', mktime(12, 2, 1, 11, 25, 2026));
        $data = $this->generateItem($item);

        $this->assertSame('http://www.example.com/mp3/live/episode.m4a', (string) $data->guid);
        $this->assertSame('true', (string) $data->guid->attributes()->isPermaLink);
    }

    public function test_date_guid_contains_the_date_and_filename(): void
    {
        RSS_File_Item::$GUID_SOURCE = 'date';
        $mtime = mktime(12, 2, 1, 11, 25, 2026);

        $item = $this->makeItem('live/episode.m4a', $mtime);
        $data = $this->generateItem($item);

        $this->assertSame(gmdate('Y-m-d', $mtime) . '-episode', (string) $data->guid);
        $this->assertSame('false', (string) $data->guid->attributes()->isPermaLink);
    }

    public function test_date_guid_is_stable_when_an_episode_moves_to_another_folder(): void
    {
        RSS_File_Item::$GUID_SOURCE = 'date';
        $mtime = mktime(12, 2, 1, 11, 25, 2026);

        // The same episode, before and after the archive moves it to 'previous'.
        $live = $this->makeItem('live/episode.m4a', $mtime);
        $previous = $this->makeItem('previous/episode.m4a', $mtime);

        $this->assertSame($live->getGuid(), $previous->getGuid());
    }

    public function test_date_guid_changes_for_the_next_week_with_the_same_filename(): void
    {
        RSS_File_Item::$GUID_SOURCE = 'date';

        $week1 = $this->makeItem('live/episode.m4a', mktime(12, 2, 1, 11, 25, 2026));
        $first_guid = $week1->getGuid();

        // Same filename, but the file for next week's episode was published a
        // week later. It must be a different episode for podcast clients.
        $week2 = $this->makeItem('live/episode.m4a', mktime(12, 2, 1, 12, 2, 2026));

        $this->assertNotSame($first_guid, $week2->getGuid());
    }

    public function test_sidecar_files_do_not_change_the_date_guid(): void
    {
        RSS_File_Item::$GUID_SOURCE = 'date';
        $mtime = mktime(12, 2, 1, 11, 25, 2026);

        $item = $this->makeItem('live/episode.m4a', $mtime);
        $before = $item->getGuid();

        // New cover art and a new summary must not change the episode identity.
        file_put_contents(self::$root . '/live/episode.jpg', 'x');
        file_put_contents(self::$root . '/live/episode.txt', 'new summary');
        touch(self::$root . '/live/episode.jpg', $mtime + 3600);
        touch(self::$root . '/live/episode.txt', $mtime + 3600);
        clearstatcache();

        $this->assertSame($before, $item->getGuid());
    }

    public function test_manual_guid_overrides_the_source(): void
    {
        RSS_File_Item::$GUID_SOURCE = 'date';

        $item = $this->makeItem('live/episode.m4a', mktime(12, 2, 1, 11, 25, 2026));
        $item->setGuid('my-own-id');
        $data = $this->generateItem($item);

        $this->assertSame('my-own-id', (string) $data->guid);
        $this->assertSame('false', (string) $data->guid->attributes()->isPermaLink);

        $item->setGuidIsPermaLink(true);
        $data = $this->generateItem($item);
        $this->assertSame('true', (string) $data->guid->attributes()->isPermaLink);
    }

    private function makeItem(string $relative_path, int $mtime): RSS_File_Item
    {
        $filename = self::$root . '/' . $relative_path;
        $directory = dirname($filename);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        file_put_contents($filename, 'not really audio');
        touch($filename, $mtime);
        clearstatcache();

        return new RSS_File_Item($filename);
    }

    private function generateItem(RSS_File_Item $item): SimpleXMLElement
    {
        $mp = new MyPodcast();
        $mp->addRssItem($item);

        $data = simplexml_load_string($mp->generate(), 'SimpleXMLElement', LIBXML_NOCDATA);
        return $data->channel->item[0];
    }

    private static function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . '/' . $entry;
            if (is_dir($path)) {
                self::removeDir($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}
