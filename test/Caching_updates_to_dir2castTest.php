<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class Caching_updates_to_dir2castTest extends TestCase
{
    public $file = 'out.xml';
    public $output = '';
    public $returncode = 0;

    public $content = '';

    public function temp_xml_glob()
    {
        return '.' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . '*.xml';
    }

    public function setUp(): void
    {
        prepare_testing_dir();

        if(is_link('dir2cast.php')) {
            // replace symlink with a real file for this test, if it isn't already.
            // It will generally be a symlink if we're doing code coverage (i.e. not on Windows)
            $source_file = readlink('dir2cast.php');
            unlink('dir2cast.php');
            copy($source_file, 'dir2cast.php');
        }

        if(file_exists('dir2cast.ini')) {
            // interferes with this test.
            unlink('dir2cast.ini');
        }

        exec('php dir2cast.php --output=out.xml --dont-uncache --ignore-dir2cast-mtime', $this->output, $this->returncode);
        $this->content = file_get_contents($this->file);

        age_dir_by('.', 86400);
    }

    public function test_update_to_dir2cast_php_invalidates_cache(): void
    {
        file_put_contents('empty.mp3', 'test');

        $cached_output_files = glob(temp_xml_glob());

        touch('dir2cast.php', time()-3600); // older than the minimum cache time, but newer than the cache files

        clearstatcache();
        $old_mtime = filemtime($cached_output_files[0]);

        sleep(1);
        exec('php dir2cast.php --output=out.xml --dont-uncache --min-file-age=30 --debug', $debug_out);
        // print(implode("\n", $debug_out));

        // passthru('ls -laR');

        clearstatcache();
        $cached_output_files = glob(temp_xml_glob());
        $new_mtime = filemtime($cached_output_files[0]);

        // cache file should be refreshed
        $this->assertGreaterThan($old_mtime, $new_mtime);

        $new_content = file_get_contents($this->file);
        $this->assertNotEquals($this->content, $new_content);

        // empty.mp3 still too new
        $this->assertEquals(0, preg_match('/empty\.mp3/', $new_content));
    }

    public function test_update_to_dir2cast_ini_invalidates_cache(): void
    {
        file_put_contents('empty.mp3', 'test');

        copy('../../dir2cast.ini', 'dir2cast.ini');

        $cached_output_files = glob(temp_xml_glob());

        touch('dir2cast.ini', time()-3600); // older than the minimum cache time, but newer than the cache files

        clearstatcache();
        $old_mtime = filemtime($cached_output_files[0]);

        sleep(1);
        exec('php dir2cast.php --output=out.xml --dont-uncache --min-file-age=30 --debug', $debug_out);
        // print(implode("\n", $debug_out));

        clearstatcache();
        $cached_output_files = glob(temp_xml_glob());
        $new_mtime = filemtime($cached_output_files[0]);

        // cache file should be refreshed
        $this->assertGreaterThan($old_mtime, $new_mtime);

        $new_content = file_get_contents($this->file);
        $this->assertNotEquals($this->content, $new_content);

        // empty.mp3 still too new
        $this->assertEquals(0, preg_match('/empty\.mp3/', $new_content));
    }

    public function tearDown(): void
    {
        unlink($this->file);
        chdir('..');
    }
}
