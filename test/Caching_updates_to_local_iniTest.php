<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class Caching_updates_to_local_iniTest extends TestCase
{
    public $file = 'out.xml';
    public $output = '';
    public $returncode = 0;

    public $content = '';

    public function setUp(): void
    {
        prepare_testing_dir();

        if(is_link('dir2cast.php')) {
            $source_file = readlink('dir2cast.php');
            unlink('dir2cast.php');
            copy($source_file, 'dir2cast.php');
        }

        // No global ini for this test - we want to test only the per-MP3-dir ini.
        if(file_exists('dir2cast.ini')) {
            unlink('dir2cast.ini');
        }

        // Pre-populate the media dir with one episode old enough to make it into the
        // feed. We deliberately do NOT age_dir_by(.) here, because doing so would
        // change empty.mp3's mtime and thus the item_hash on the next scan, which
        // would invalidate the cache for reasons unrelated to the local ini.
        mkdir('media');
        copy('../fixtures/empty.mp3', 'media/empty.mp3');
        touch('media/empty.mp3', time() - 86400);

        // First-pass cache must reflect default settings (no local ini yet).
        exec('php dir2cast.php --media-dir=media --output=out.xml --dont-uncache --min-file-age=30 --ignore-dir2cast-mtime', $this->output, $this->returncode);
        $this->content = file_get_contents($this->file);

        // Sanity check: the feed used the default language.
        $this->assertStringContainsString('<language>en-us</language>', $this->content);
    }

    public function test_per_dir_ini_change_invalidates_cache(): void
    {
        // Drop a per-MP3-dir ini that overrides LANGUAGE. Touch it newer than the
        // cached output but with empty.mp3's mtime untouched (so item_hash stays
        // the same). The only signal the cache layer has to regenerate is that
        // the local ini was modified - which is exactly what we want to verify.
        file_put_contents('media/dir2cast.ini', "LANGUAGE = \"fr-fr\"\n");

        $cache_files = glob('temp' . DIRECTORY_SEPARATOR . '*.xml');
        $this->assertNotEmpty($cache_files);
        $cache_file = $cache_files[0];

        // We deliberately do NOT use --ignore-dir2cast-mtime here, so the dispatcher
        // tracks both dir2cast.php's mtime and the local ini's mtime. To prove the
        // ini specifically is what triggers invalidation, age dir2cast.php older
        // than the cache: the only signal newer than the cache is the ini.
        $now = time();
        touch('dir2cast.php', $now - 86400);
        touch($cache_file, $now - 1000);
        touch('media/dir2cast.ini', $now - 30);
        clearstatcache();

        exec('php dir2cast.php --media-dir=media --output=out.xml --dont-uncache --min-file-age=30 --debug', $debug_out);

        $new_content = file_get_contents($this->file);
        $this->assertStringContainsString(
            '<language>fr-fr</language>',
            $new_content,
            "Local dir2cast.ini change must invalidate the cache. Debug output:\n" . implode("\n", $debug_out)
        );
    }

    public function tearDown(): void
    {
        file_exists($this->file) && unlink($this->file);
        chdir('..');
    }
}
