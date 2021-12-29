<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CachingTest extends TestCase
{
    public $file = 'out.xml';
    public $output = '';
    public $returncode = 0;

    public $content = '';

    public function setUp(): void
    {
        prepare_testing_dir();
        exec('php dir2cast.php --output=out.xml --dont-uncache', $this->output, $this->returncode);
        $this->content = file_get_contents($this->file);
        clearstatcache();
    }

    public function test_age_dir()
    {
        $mtime1 = filemtime('dir2cast.php');
        $mtime2 = filemtime('temp');
        $mtime3 = filemtime('.');
        $mtime4 = filemtime('out.xml');

        age_dir_by('.', 10);

        $this->assertEquals($mtime1 - 10, filemtime('dir2cast.php'));
        $this->assertEquals($mtime2 - 10, filemtime('temp'));
        $this->assertEquals($mtime3 - 10, filemtime('.'));
        $this->assertEquals($mtime4 - 10, filemtime('out.xml'));
    }

    public function test_default_empty_podcast_caches_output_in_default_folder(): void
    {
        // caches the output in the default temp folder
        $this->assertTrue(is_dir('./temp'));
        $cached_output_files = glob('./temp/*.xml');
        $this->assertCount(1, $cached_output_files);

        // caches what was generated
        $this->assertSame(
            $this->content,
            file_get_contents($cached_output_files[0])
        );
    }

    public function test_default_empty_podcast_doesnt_regenerate_in_first_MIN_CACHE_TIME(): void
    {
        $cached_output_files = glob('./temp/*.xml');
        age_dir_by('.', 7);

        // bring cache within last 5 seconds
        touch($cached_output_files[0], time());

        clearstatcache();
        $cached_mtime_before = filemtime($cached_output_files[0]);

        exec('php dir2cast.php --output=out.xml --dont-uncache', $new_output, $this->returncode);

        clearstatcache();
        $cached_mtime_after = filemtime($cached_output_files[0]);

        $this->assertSame(
            $cached_mtime_before,
            $cached_mtime_after
        );

        $new_content = file_get_contents($this->file);
        $this->assertSame(
            $this->content,
            $new_content
        );
    }

    public function test_default_empty_podcast_renews_cache_file_mtime_after_MIN_CACHE_TIME(): void
    {
        $cached_output_files = glob('./temp/*.xml');
        age_dir_by('.', 7);

        // leave cache file 7 seconds ago (default threshold is 5 seconds)

        clearstatcache();
        $cached_mtime_before = filemtime($cached_output_files[0]);

        exec('php dir2cast.php --output=out.xml --dont-uncache', $new_output, $this->returncode);

        clearstatcache();
        $cached_mtime_after = filemtime($cached_output_files[0]);

        $this->assertNotSame(
            $cached_mtime_before,
            $cached_mtime_after
        );

        $new_content = file_get_contents($this->file);
        $this->assertSame(
            $this->content,
            $new_content
        );
    }

    public function test_default_empty_podcast_doesnt_regenerate_before_MIN_CACHE_TIME_with_a_change(): void
    {
        $cached_output_files = glob('./temp/*.xml');
        age_dir_by('.', 7);

        // bring cache within last 5 seconds
        touch($cached_output_files[0], time());

        // newer than the cache file, but older than MIN_CACHE_TIME
        file_put_contents('empty.mp3', 'test');
        touch('empty.mp3', time()); // NOOP, but here for symmetry

        clearstatcache();
        $cached_mtime_before = filemtime($cached_output_files[0]);

        exec('php dir2cast.php --output=out.xml --dont-uncache', $new_output, $this->returncode);

        clearstatcache();
        $cached_mtime_after = filemtime($cached_output_files[0]);

        $this->assertSame(
            $cached_mtime_before,
            $cached_mtime_after
        );

        $new_content = file_get_contents($this->file);
        $this->assertSame(
            $this->content,
            $new_content
        );

        $this->assertEquals(0, preg_match('/empty\.mp3/', $new_content));
    }    

    public function test_default_empty_podcast_regenerates_after_MIN_CACHE_TIME_with_a_change(): void
    {
        $cached_output_files = glob('./temp/*.xml');
        age_dir_by('.', 90);

        // leave cache file 7 seconds ago (default threshold is 5 seconds)

        // newer than the cache file, but older than MIN_CACHE_TIME
        file_put_contents('empty.mp3', 'test');
        touch('empty.mp3', time() - 35);

        clearstatcache();
        $cached_mtime_before = filemtime($cached_output_files[0]);

        exec('php dir2cast.php --output=out.xml --dont-uncache', $new_output, $this->returncode);

        clearstatcache();
        $cached_mtime_after = filemtime($cached_output_files[0]);

        $this->assertNotSame(
            $cached_mtime_before,
            $cached_mtime_after
        );

        $new_content = file_get_contents($this->file);
        $this->assertNotSame(
            $this->content,
            $new_content
        );

        $this->assertEquals(1, preg_match('/empty\.mp3/', file_get_contents($this->file)));

    }

    public function test_default_empty_podcast_obeys_minimum_cache_time_not_elapsed(): void
    {
        age_dir_by('.', 2);

        // new enough to bust the cache, but cache is very new
        file_put_contents('empty.mp3', 'test');

        // --dont-uncache: tells dir2cast not use the default caching rules, not ignore them due to CLI
        // --min-file-age=0 : tells dir2cast to include files that are brand new
        exec('php dir2cast.php --output=out.xml --dont-uncache --min-file-age=0', $this->output, $this->returncode);

        $new_content = file_get_contents($this->file);
        $this->assertEquals($this->content, $new_content);
        $this->assertEquals(0, preg_match('/empty\.mp3/', $new_content));
    }

    public function test_default_empty_podcast_obeys_minimum_cache_time_elapsed(): void
    {
        age_dir_by('.', 3600);

        // new enough to bust the cache
        file_put_contents('empty.mp3', 'test');

        // --dont-uncache: tells dir2cast not use the default caching rules, not ignore them due to CLI
        // --min-file-age=0 : tells dir2cast to include files that are brand new
        exec('php dir2cast.php --output=out.xml --dont-uncache --min-file-age=0', $this->output, $this->returncode);

        $new_content = file_get_contents($this->file);
        $this->assertNotEquals($this->content, $new_content);
        $this->assertEquals(1, preg_match('/empty\.mp3/', $new_content));
    }

    public function test_default_empty_podcast_uncaches_without_dont_uncache_even_if_not_elapsed(): void
    {
        age_dir_by('.', 2);

        // too new to bust the cache, but cli runner uncaches anyway
        file_put_contents('empty.mp3', 'test');

        exec('php dir2cast.php --output=out.xml --min-file-age=0', $this->output, $this->returncode);

        $new_content = file_get_contents($this->file);
        $this->assertNotEquals($this->content, $new_content);
        $this->assertEquals(1, preg_match('/empty\.mp3/', $new_content));
    }

    public function test_expired_podcast_is_regenerated(): void
    {
        age_dir_by('.', 86400);

        $cached_output_files = glob('./temp/*.xml');

        clearstatcache();
        $old_mtime = filemtime($cached_output_files[0]);

        file_put_contents('empty.mp3', 'test');
        touch('empty.mp3', time()-3600); // busts cache as older than min-file-age

        exec('php dir2cast.php --output=out.xml --dont-uncache --min-file-age=30');
        $new_content = file_get_contents($this->file); // should have empty.mp3
        $this->assertNotEquals($this->content, $new_content);
        $this->assertEquals(1, preg_match('/empty\.mp3/', $new_content));

        clearstatcache();
        $new_mtime = filemtime($cached_output_files[0]);
        
        // cache file should be refreshed
        $this->assertNotEquals($old_mtime, $new_mtime);
    }

    public function test_too_new_file_not_included_in_podcast(): void
    {
        age_dir_by('.', 86400);

        $cached_output_files = glob('./temp/*.xml');

        clearstatcache();
        $old_mtime = filemtime($cached_output_files[0]);

        file_put_contents('empty.mp3', 'test');
        touch('empty.mp3'); // too new to be included because of min-file-age, but still busts cache
        // FIXME: its presence busts the cache anyway, which is not how it's supposed to work

        exec('php dir2cast.php --output=out.xml --dont-uncache --min-file-age=30');
        $new_content = file_get_contents($this->file); // should not have empty.mp3

        # Because the code coverage harness is slow, the build date might be one second out.
        # We can just not compare that part and the test is still essentially valid.
        // $old_content = preg_replace('#<lastBuildDate>[^<]+</lastBuildDate>#', '', $this->content);
        // $new_content = preg_replace('#<lastBuildDate>[^<]+</lastBuildDate>#', '', $new_content);
        # TODO: improve this so that it actually serves the cached content instead of regenerating

        $this->assertEquals($this->content, $new_content);
        $this->assertEquals(0, preg_match('/empty\.mp3/', $new_content));

        clearstatcache();
        $new_mtime = filemtime($cached_output_files[0]);

        // cache file should be refreshed
        $this->assertNotEquals($old_mtime, $new_mtime);
    }

    public function test_update_to_dir2cast_php_invalidates_cache(): void
    {
        file_put_contents('empty.mp3', 'test');

        unlink('dir2cast.php');
        copy('../../dir2cast.php', 'dir2cast.php');
        copy('../../dir2cast.ini', 'dir2cast.ini');

        age_dir_by('.', 86400);

        $cached_output_files = glob('./temp/*.xml');

        touch('dir2cast.php', time()-3600); // older than the minimum cache time, but newer than the cache files

        clearstatcache();
        $old_mtime = filemtime($cached_output_files[0]);

        exec('php dir2cast.php --output=out.xml --dont-uncache --min-file-age=30');
        $new_content = file_get_contents($this->file); // should have empty.mp3
        $this->assertNotEquals($this->content, $new_content);
        $this->assertEquals(1, preg_match('/empty\.mp3/', $new_content));

        clearstatcache();
        $new_mtime = filemtime($cached_output_files[0]);

        // cache file should be refreshed
        $this->assertNotEquals($old_mtime, $new_mtime);
    }

    public function test_update_to_dir2cast_ini_invalidates_cache(): void
    {
        file_put_contents('empty.mp3', 'test');

        unlink('dir2cast.php');
        copy('../../dir2cast.php', 'dir2cast.php');
        copy('../../dir2cast.ini', 'dir2cast.ini');

        age_dir_by('.', 86400);

        $cached_output_files = glob('./temp/*.xml');

        touch('dir2cast.ini', time()-3600); // older than the minimum cache time, but newer than the cache files

        clearstatcache();
        $old_mtime = filemtime($cached_output_files[0]);

        exec('php dir2cast.php --output=out.xml --dont-uncache --min-file-age=30');
        $new_content = file_get_contents($this->file); // should have empty.mp3
        $this->assertNotEquals($this->content, $new_content);
        $this->assertEquals(1, preg_match('/empty\.mp3/', $new_content));

        clearstatcache();
        $new_mtime = filemtime($cached_output_files[0]);

        // cache file should be refreshed
        $this->assertNotEquals($old_mtime, $new_mtime);
    }

    public function tearDown(): void
    {
        chdir('..');
    }
}
