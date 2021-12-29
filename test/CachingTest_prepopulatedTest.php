<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class CachingTest_prepopulatedTest extends CachingTest
{
    public function setUp(): void
    {
        prepare_testing_dir();
        file_put_contents('pre-existing.mp3', 'test');
        touch('pre-existing.mp3', time()-86400);
        exec('php dir2cast.php --output=out.xml --dont-uncache --ignore-dir2cast-mtime', $this->output, $this->returncode);
        $this->content = file_get_contents($this->file);
        clearstatcache();
    }

    public function tearDown(): void
    {
        $this->assertEquals(1, preg_match('/pre-existing\.mp3/', file_get_contents($this->file)));
        parent::tearDown();
    }
}
