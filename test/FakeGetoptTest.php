<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class FakeGetoptTest extends TestCase
{
    public function test_fake_getopt_no_args()
    {
        $this->assertEquals(
            fake_getopt(array('php', '--halp'), '', array()),
            array()
        );
        $this->assertEquals(
            fake_getopt(array('php'), '', array()),
            array()
        );
    }    
    public function test_fake_getopt_no_match()
    {
        $this->assertEquals(
            fake_getopt(array('php', '--halp'), '', array('help')),
            array()
        );
        $this->assertEquals(
            fake_getopt(array('php'), '', array('help')),
            array()
        );
    }

    public function test_fake_getopt_bool_arg()
    {
        $this->assertEquals(
            fake_getopt(array('php', '--help'), '', array('help')),
            array('help' => false)
        );
    }
    public function test_fake_getopt_string_arg()
    {
        $this->assertEquals(
            fake_getopt(array('php', '--media-dir'), '', array('media-dir::')),
            array('media-dir' => '')
        );
        $this->assertEquals(
            fake_getopt(array('php', '--media-dir='), '', array('media-dir::')),
            array() // XXX: seems to be a bug in getopt
        );
        $this->assertEquals(
            fake_getopt(array('php', '--media-dir=test'), '', array('media-dir::')),
            array('media-dir' => 'test')
        );
    }
    public function test_fake_getopt_escaping()
    {
        $this->assertEquals(
            fake_getopt(array('php', "--media-dir= "), '', array('media-dir::')),
            array('media-dir' => ' ')
        );
        $this->assertEquals(
            fake_getopt(array('php', '--media-dir=""'), '', array('media-dir::')),
            array('media-dir' => '""')
        );
        $this->assertEquals(
            fake_getopt(array('php', "--media-dir=''"), '', array('media-dir::')),
            array('media-dir' => "''")
        );
    }
    public function test_fake_getopt_both_arg_types()
    {
        $this->assertEquals(
            fake_getopt(array('php', '--help', '--media-dir'), '', array('help', 'media-dir::')),
            array('help' => false, 'media-dir' => '')
        );
    }
}
