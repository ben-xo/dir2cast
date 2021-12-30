<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class Caching_updates_to_dir2cast_prepopulatedTest extends Caching_updates_to_dir2castTest
{
    public function setUp(): void
    {
        prepare_testing_dir();
        file_put_contents('pre-existing.mp3', 'test');
        touch('pre-existing.mp3', time()-86400);

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

    public function tearDown(): void
    {
        $this->assertEquals(1, preg_match('/pre-existing\.mp3/', file_get_contents($this->file)));
        parent::tearDown();
    }
}
