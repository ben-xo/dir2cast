<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DefaultsTest extends TestCase
{
    public $tmpdir;

    public function setUp(): void
    {
        is_dir('./testdir') && rmrf('./testdir');
        mkdir('./testdir');
        copy('../dir2cast.php', './testdir/dir2cast.php');
        chdir('./testdir');
    }

    public function test_default_empty_podcast(): void
    {
        $output = array();
        $returncode = false;
        exec('php dir2cast.php --output=out.xml', $output, $returncode);
        $this->assertSame(
            'Writing RSS to: out.xml\n** Warning: generated podcast found no episodes.',
            implode('\n', $output)
        );
        $this->assertSame(255, $returncode);
    }

    public function tearDown(): void
    {
        chdir('..');
        // rmrf('./testdir');
    }

}
