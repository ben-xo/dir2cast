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
        exec('php dir2cast.php --output=out.xml --media-dir=imaginary-dir', $output, $returncode);
        $this->assertEquals("Not Found: imaginary-dir", implode("\n", $output));
        $this->assertEquals(254, $returncode);  // 254 is -2
    }

    public static function tearDownAfterClass(): void
    {
        chdir('..');
    }
}
