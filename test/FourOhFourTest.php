<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class FourOhFourTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        prepare_testing_dir();
    }

    public function test_non_existent_dir_prints_bare_error_CLI_case(): void
    {
        exec('php dir2cast.php --media-dir=dir2cast.ini', $output, $returncode);
        $this->assertEquals("Not Found: dir2cast.ini", implode("\n", $output));
        $this->assertEquals(254, $returncode);  // 254 is -2
    }

    public static function tearDownAfterClass(): void
    {
        chdir('..');
    }
}
