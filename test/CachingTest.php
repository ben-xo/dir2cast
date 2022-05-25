<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class CachingTest extends TestCase
{
    public $file = 'out.xml';
    public $output = '';
    public $returncode = 0;

    public $content = '';

    public function setUp(): void
    {
        prepare_testing_dir();
        exec('php dir2cast.php --output=out.xml --dont-uncache --ignore-dir2cast-mtime', $this->output, $this->returncode);
        $this->content = file_get_contents($this->file);
        clearstatcache();
    }

    public function test_default_empty_podcast_caches_output_in_default_folder(): void
    {
        // caches the output in the default temp folder
        $this->assertTrue(is_dir('./temp'));
        $cached_output_files = glob(temp_xml_glob());
        $this->assertCount(1, $cached_output_files);

        // caches what was generated
        $this->assertSame(
            $this->content,
            file_get_contents($cached_output_files[0])
        );
    }

    public function test_default_empty_podcast_doesnt_regenerate_in_first_MIN_CACHE_TIME(): void
    {
        $cached_output_files = glob(temp_xml_glob());
        age_dir_by('.', 7);

        // bring cache within last 5 seconds
        touch($cached_output_files[0], time());

        clearstatcache();
        $cached_mtime_before = filemtime($cached_output_files[0]);

        exec('php dir2cast.php --output=out.xml --dont-uncache --ignore-dir2cast-mtime --clock-offset=7', $new_output, $this->returncode);

        clearstatcache();
        $cached_output_files = glob(temp_xml_glob());
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
        $cached_output_files = glob(temp_xml_glob());
        age_dir_by('.', 7);

        // leave cache file 7 seconds ago (default threshold is 5 seconds)

        clearstatcache();
        $cached_mtime_before = filemtime($cached_output_files[0]);

        exec('php dir2cast.php --output=out.xml --dont-uncache --ignore-dir2cast-mtime --clock-offset=7', $new_output, $this->returncode);

        clearstatcache();
        $cached_output_files = glob(temp_xml_glob());
        $cached_mtime_after = filemtime($cached_output_files[0]);

        $this->assertGreaterThan(
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
        $cached_output_files = glob(temp_xml_glob());
        age_dir_by('.', 7);

        // bring cache within last 5 seconds
        touch($cached_output_files[0], time());

        // newer than the cache file, but older than MIN_CACHE_TIME
        file_put_contents('empty.mp3', 'test');
        touch('empty.mp3', time()); // NOOP, but here for symmetry

        clearstatcache();
        $cached_mtime_before = filemtime($cached_output_files[0]);

        exec('php dir2cast.php --output=out.xml --dont-uncache --ignore-dir2cast-mtime --clock-offset=7', $new_output, $this->returncode);

        clearstatcache();
        $cached_output_files = glob(temp_xml_glob());
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
        $cached_output_files = glob(temp_xml_glob());
        age_dir_by('.', 90);

        // leave cache file 7 seconds ago (default threshold is 5 seconds)

        // newer than the cache file, but older than MIN_CACHE_TIME
        file_put_contents('empty.mp3', 'test');
        touch('empty.mp3', time() - 35);

        clearstatcache();
        $cached_mtime_before = filemtime($cached_output_files[0]);

        exec('php dir2cast.php --output=out.xml --dont-uncache --ignore-dir2cast-mtime --clock-offset=90', $new_output, $this->returncode);

        clearstatcache();
        $cached_output_files = glob(temp_xml_glob());
        $cached_mtime_after = filemtime($cached_output_files[0]);

        $this->assertGreaterThan(
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
        exec('php dir2cast.php --output=out.xml --dont-uncache --min-file-age=0 --ignore-dir2cast-mtime --clock-offset=2', $this->output, $this->returncode);

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
        exec('php dir2cast.php --output=out.xml --dont-uncache --min-file-age=0 --ignore-dir2cast-mtime --clock-offset=3600', $this->output, $this->returncode);

        $new_content = file_get_contents($this->file);
        $this->assertNotEquals($this->content, $new_content);
        $this->assertEquals(1, preg_match('/empty\.mp3/', $new_content));
    }

    public function test_default_empty_podcast_uncaches_without_dont_uncache_even_if_not_elapsed(): void
    {
        age_dir_by('.', 2);

        // too new to bust the cache, but cli runner uncaches anyway
        file_put_contents('empty.mp3', 'test');

        exec('php dir2cast.php --output=out.xml --min-file-age=0 --ignore-dir2cast-mtime --clock-offset=2', $this->output, $this->returncode);

        $new_content = file_get_contents($this->file);
        $this->assertNotEquals($this->content, $new_content);
        $this->assertEquals(1, preg_match('/empty\.mp3/', $new_content));
    }

    public function test_expired_podcast_is_regenerated(): void
    {
        age_dir_by('.', 86400);

        $cached_output_files = glob(temp_xml_glob());

        clearstatcache();
        $old_mtime = filemtime($cached_output_files[0]);

        file_put_contents('empty.mp3', 'test');
        touch('empty.mp3', time()-3600); // busts cache as older than min-file-age

        exec('php dir2cast.php --output=out.xml --dont-uncache --min-file-age=30 --ignore-dir2cast-mtime --clock-offset=86400');
        $new_content = file_get_contents($this->file); // should have empty.mp3
        $this->assertNotEquals($this->content, $new_content);
        $this->assertEquals(1, preg_match('/empty\.mp3/', $new_content));

        clearstatcache();
        $cached_output_files = glob(temp_xml_glob());
        $new_mtime = filemtime($cached_output_files[0]);
        
        // cache file should be refreshed
        $this->assertGreaterThan($old_mtime, $new_mtime);
    }

    public function test_too_new_file_not_included_in_podcast(): void
    {
        age_dir_by('.', 86400);

        $cached_output_files = glob(temp_xml_glob());

        clearstatcache();
        $old_mtime = filemtime($cached_output_files[0]);

        file_put_contents('empty.mp3', 'test');
        touch('empty.mp3'); // too new to be included because of min-file-age

        // this sleep guarantees that the Last Modified will be different IF the feed is regenerated
        // (which it shouldn't be!)
        sleep(1);

        exec('php dir2cast.php --output=out.xml --dont-uncache --min-file-age=30 --ignore-dir2cast-mtime --clock-offset=86400');

        $new_content = file_get_contents($this->file); // should not have empty.mp3

        $this->assertEquals($this->content, $new_content);
        $this->assertEquals(0, preg_match('/empty\.mp3/', $new_content));

        clearstatcache();
        $cached_output_files = glob(temp_xml_glob());
        $new_mtime = filemtime($cached_output_files[0]);

        // cache file should be refreshed
        $this->assertGreaterThan($old_mtime, $new_mtime);
    }

    public function tearDown(): void
    {
        unlink($this->file);
        chdir('..');
    }
}
