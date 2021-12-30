<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class Caching_updates_to_feed_metadataTest extends TestCase
{
    public $file = 'out.xml';
    public $output = '';
    public $returncode = 0;

    public $content = '';

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

    protected function _update_feed()
    {
        $cached_output_files = glob(temp_xml_glob());

        clearstatcache();
        $old_mtime = filemtime($cached_output_files[0]);

        exec('php dir2cast.php --output=out.xml --dont-uncache --ignore-dir2cast-mtime --min-file-age=0 --debug', $debug_out);
        // print(implode("\n", $debug_out));

        clearstatcache();
        $cached_output_files = glob(temp_xml_glob());
        $new_mtime = filemtime($cached_output_files[0]);

        // cache file should be refreshed
        $this->assertGreaterThan($old_mtime, $new_mtime);

        $new_content = file_get_contents($this->file);

        return $new_content;
    }

    public function test_update_to_description_txt_invalidates_cache(): void
    {
        file_put_contents('description.txt', 'Magic Word');
        $new_content = $this->_update_feed();
        $this->assertNotEquals($this->content, $new_content);
        $this->assertEquals(0, preg_match('/Magic Word/', $this->content));
        $this->assertEquals(1, preg_match('/Magic Word/', $new_content));
    }

    public function test_update_to_itunes_summary_invalidates_cache(): void
    {
        file_put_contents('itunes_summary.txt', 'Magic Word');
        $new_content = $this->_update_feed();
        $this->assertNotEquals($this->content, $new_content);
        $this->assertEquals(0, preg_match('/Magic Word/', $this->content));
        $this->assertEquals(1, preg_match('/Magic Word/', $new_content));
     }

    public function test_update_to_itunes_subtitle_invalidates_cache(): void
    {
        file_put_contents('itunes_subtitle.txt', 'Magic Word');
        $new_content = $this->_update_feed();
        $this->assertNotEquals($this->content, $new_content);
        $this->assertEquals(0, preg_match('/Magic Word/', $this->content));
        $this->assertEquals(1, preg_match('/Magic Word/', $new_content));
    }

    public function test_update_to_image_jpg_invalidates_cache(): void
    {
        file_put_contents('image.jpg', 'Magic Word');
        $new_content = $this->_update_feed();
        $this->assertNotEquals($this->content, $new_content);
        $this->assertEquals(0, preg_match('/image\.jpg/', $this->content));
        $this->assertEquals(1, preg_match('/image\.jpg/', $new_content));
    }

    public function test_update_to_image_png_invalidates_cache(): void
    {
        file_put_contents('image.png', 'Magic Word');
        $new_content = $this->_update_feed();
        $this->assertNotEquals($this->content, $new_content);
        $this->assertEquals(0, preg_match('/image\.png/', $this->content));
        $this->assertEquals(1, preg_match('/image\.png/', $new_content));
    }

    public function test_update_to_itunes_image_jpg_invalidates_cache(): void
    {
        file_put_contents('itunes_image.jpg', 'Magic Word');
        $new_content = $this->_update_feed();
        $this->assertNotEquals($this->content, $new_content);
        $this->assertEquals(0, preg_match('/itunes_image\.jpg/', $this->content));
        $this->assertEquals(1, preg_match('/itunes_image\.jpg/', $new_content));
    }

    public function test_update_to_itunes_image_png_invalidates_cache(): void
    {
        file_put_contents('itunes_image.png', 'Magic Word');
        $new_content = $this->_update_feed();
        $this->assertNotEquals($this->content, $new_content);
        $this->assertEquals(0, preg_match('/itunes_image\.png/', $this->content));
        $this->assertEquals(1, preg_match('/itunes_image\.png/', $new_content));
    }

    public function test_image_ignored_if_ancient(): void
    {
        file_put_contents('image.jpg', 'Magic Word');
        touch('image.jpg', time() - 86400 - 60);

        $new_content = $this->_update_feed();
        $this->assertEquals($this->content, $new_content);

        $this->assertEquals(0, preg_match('/image\.png/', $this->content));
        $this->assertEquals(0, preg_match('/image\.jpg/', $this->content));

        // an update to png was detected, but jpg still outranks png if both exist
        $this->assertEquals(0, preg_match('/image\.png/', $new_content));
        $this->assertEquals(0, preg_match('/image\.jpg/', $new_content));
    }    

    public function test_png_ignored_if_jpg_exists(): void
    {
        file_put_contents('image.png', 'Magic Word');
        file_put_contents('image.jpg', 'Magic Word');
        touch('image.jpg', time() - 86400 - 60);

        $new_content = $this->_update_feed();
        $this->assertNotEquals($this->content, $new_content);

        $this->assertEquals(0, preg_match('/image\.png/', $this->content));
        $this->assertEquals(0, preg_match('/image\.jpg/', $this->content));

        // an update to png was detected, but jpg still outranks png if both exist
        $this->assertEquals(0, preg_match('/image\.png/', $new_content));
        $this->assertEquals(1, preg_match('/image\.jpg/', $new_content));
    }

    public function tearDown(): void
    {
        unlink($this->file);
        chdir('..');
    }
}
