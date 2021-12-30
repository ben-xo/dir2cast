<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AgeDirTest extends TestCase
{
    public function setUp(): void
    {
        prepare_testing_dir();
        exec('php dir2cast.php --output=out.xml --dont-uncache --ignore-dir2cast-mtime');
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

    public function tearDown(): void
    {
        chdir('..');
    }
}
