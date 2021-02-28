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
    }

    public function setUp(): void
    {
        exec('php dir2cast.php --output=out.xml --min-file-age=0', self::$output, self::$returncode);
        self::$content = file_get_contents(self::$file);
        clearstatcache();
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
        // new enough to bust the cache, but cache is very new
        file_put_contents('empty.mp3', 'test');
        touch('empty.mp3', time()-3600);
        touch('dir2cast.php', time()-86400);

        exec('php dir2cast.php --output=out.xml --dont-uncache --min-file-age=0', self::$output, self::$returncode);

        $new_content = file_get_contents(self::$file);
        $this->assertEquals(self::$content, $new_content);
    }

    public function test_default_empty_podcast_uncaches_anyway(): void
    {
        // too new to bust the cache, but cli runner uncaches anyway
        file_put_contents('empty.mp3', 'test');
        touch('empty.mp3');
        touch('dir2cast.php', time()-86400);

        exec('php dir2cast.php --output=out.xml --min-file-age=0', self::$output, self::$returncode);

        $new_content = file_get_contents(self::$file);
        $this->assertNotEquals(self::$content, $new_content);
    }

    public function test_expired_podcast_is_regenerated(): void
    {
        $cached_output_files = glob('./temp/*.xml');
        foreach ($cached_output_files as $file) {
            touch($file, time()-86400);
        }
        touch(self::$file, time()-86400);
        touch('dir2cast.php', time()-86400);

        file_put_contents('empty.mp3', 'test');
        touch('empty.mp3', time()-3600);

        clearstatcache();
        $old_mtime = filemtime($cached_output_files[0]);

        exec('php dir2cast.php --output=out.xml --dont-uncache --min-file-age=0');
        $new_content = file_get_contents(self::$file); // should have empty.mp3
        $this->assertNotEquals(self::$content, $new_content);

        clearstatcache();
        $new_mtime = filemtime($cached_output_files[0]);
        
        // cache file should be refreshed
        $this->assertNotEquals($old_mtime, $new_mtime);
    }

    public function test_too_new_file_not_included_in_podcast(): void
    {
        // make cache files and output file a day old
        $cached_output_files = glob('./temp/*.xml');
        foreach ($cached_output_files as $file) {
            touch($file, time()-86400);
        }
        touch(self::$file, time()-86400);
        touch('dir2cast.php', time()-86400);

        file_put_contents('empty.mp3', 'test');
        touch('empty.mp3'); // too new to bust cache

        clearstatcache();
        $old_mtime = filemtime($cached_output_files[0]);

        exec('php dir2cast.php --output=out.xml --dont-uncache --min-file-age=30');
        $new_content = file_get_contents(self::$file); // should not have empty.mp3

        # Because the code coverage harness is slow, the build date might be one second out.
        # We can just not compare that part and the test is still essentially valid.
        $old_content = preg_replace('#<lastBuildDate>[^<]+</lastBuildDate>\n#', '', self::$content);
        $new_content = preg_replace('#<lastBuildDate>[^<]+</lastBuildDate>\n#', '', $new_content);
        # TODO: improve this so that it actually serves the cached content instead of regenerating

        $this->assertEquals($old_content, $new_content);

        clearstatcache();
        $new_mtime = filemtime($cached_output_files[0]);

        // cache file should be refreshed
        $this->assertNotEquals($old_mtime, $new_mtime);
    }

    public function test_update_to_dir2cast_php_invalidates_cache(): void
    {
        // make cache files and output file a day old
        $cached_output_files = glob('./temp/*.xml');
        foreach ($cached_output_files as $file) {
            touch($file, time()-86400);
        }
        touch(self::$file, time()-86400);
        touch('../../dir2cast.php', time()-3600); // older than the minimum cache time, but newer than the cache files

        file_put_contents('empty.mp3', 'test');
        touch('empty.mp3', time()-86400);

        clearstatcache();
        $old_mtime = filemtime($cached_output_files[0]);

        exec('php dir2cast.php --output=out.xml --dont-uncache --min-file-age=30');
        $new_content = file_get_contents(self::$file); // should have empty.mp3
        $this->assertNotEquals(self::$content, $new_content);

        clearstatcache();
        $new_mtime = filemtime($cached_output_files[0]);

        // cache file should be refreshed
        $this->assertNotEquals($old_mtime, $new_mtime);
    }

    public function test_update_to_dir2cast_ini_invalidates_cache(): void
    {
        // make cache files and output file a day old
        $cached_output_files = glob('./temp/*.xml');
        foreach ($cached_output_files as $file) {
            touch($file, time()-86400);
        }
        touch(self::$file, time()-86400);
        touch('dir2cast.php', time()-86400);
        touch('dir2cast.ini', time()-3600);

        file_put_contents('empty.mp3', 'test');
        touch('empty.mp3', time()-86400);

        clearstatcache();
        $old_mtime = filemtime($cached_output_files[0]);

        exec('php dir2cast.php --output=out.xml --dont-uncache --min-file-age=30');
        $new_content = file_get_contents(self::$file); // should have empty.mp3
        $this->assertNotEquals(self::$content, $new_content);

        clearstatcache();
        $new_mtime = filemtime($cached_output_files[0]);

        // cache file should be refreshed
        $this->assertNotEquals($old_mtime, $new_mtime);
    }

    public function tearDown(): void
    {
        file_exists('empty.mp3') && unlink('empty.mp3');
        rmrf('temp');
    }

    public static function tearDownAfterClass(): void
    {
        chdir('..');
    }

}
