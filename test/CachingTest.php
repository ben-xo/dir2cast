<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CachingTest extends TestCase
{
    public static $file = 'out.xml';
    public static $output = '';
    public static $returncode = 0;

    public static $content = '';

    public static function setUpBeforeClass(): void
    {
        prepare_testing_dir();
        exec('php dir2cast.php --output=out.xml', self::$output, self::$returncode);
        self::$content = file_get_contents(self::$file);
    }

    public function test_default_empty_podcast_caches_output_in_default_folder(): void
    {
        // caches the output in the default temp folder
        $this->assertTrue(is_dir('./temp'));
        $cached_output_files = glob('./temp/*.xml');
        $this->assertSame(1, sizeof($cached_output_files));

        // caches what was generated
        $this->assertSame(
            self::$content,
            file_get_contents($cached_output_files[0])
        );
    }

    public function test_default_empty_podcast_obeys_minimum_cache_time(): void
    {
        $this->markTestIncomplete('This test is known to fail');

        // too new to bust the cache
        file_put_contents('empty.mp3', 'test');
        touch('empty.mp3');

        exec('php dir2cast.php --output=out.xml', self::$output, self::$returncode);

        $new_content = file_get_contents(self::$file);
        $this->assertEquals(self::$content, $new_content);

        unlink('empty.mp3');
    }

    public function test_expired_podcast_is_regenerated(): void
    {
        $cached_output_files = glob('./temp/*.xml');
        foreach ($cached_output_files as $file) {
            touch($file, time()-86400);
        }
        touch(self::$file, time()-86400);

        file_put_contents('empty.mp3', 'test');
        touch('empty.mp3', time()-3600);

        clearstatcache();
        $old_mtime = filemtime($cached_output_files[0]);

        exec('php dir2cast.php --output=out.xml');
        $new_content = file_get_contents(self::$file); // should have different publishDate
        $this->assertNotEquals(self::$content, $new_content);

        clearstatcache();
        $new_mtime = filemtime($cached_output_files[0]);
        $this->assertNotEquals($old_mtime, $new_mtime);

        unlink('empty.mp3');
    }

    public function test_update_to_dir2cast_php_invalidates_cache(): void
    {
        $this->markTestIncomplete('TODO');
    }

    public function test_update_to_dir2cast_ini_invalidates_cache(): void
    {
        $this->markTestIncomplete('TODO');
    }

    public static function tearDownAfterClass(): void
    {
        chdir('..');
    }

}
