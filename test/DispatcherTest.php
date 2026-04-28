<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DispatcherTest extends TestCase
{
    private function makePodcastMock($expect_uncache)
    {
        $mock = $this->getMockBuilder(Locking_Cached_Dir_Podcast::class)
                     ->disableOriginalConstructor()
                     ->setMethods(['uncache'])
                     ->getMock();
        if($expect_uncache) {
            $mock->expects($this->once())->method('uncache');
        } else {
            $mock->expects($this->never())->method('uncache');
        }
        return $mock;
    }

    public function test_empty_password_never_uncaches()
    {
        $podcast = $this->makePodcastMock(false);
        $dispatcher = new Dispatcher($podcast);
        $dispatcher->uncache_if_forced('', array('force' => 'anything'));
    }

    public function test_missing_force_param_does_not_uncache()
    {
        $podcast = $this->makePodcastMock(false);
        $dispatcher = new Dispatcher($podcast);
        $dispatcher->uncache_if_forced('secret', array());
    }

    public function test_wrong_password_does_not_uncache()
    {
        $podcast = $this->makePodcastMock(false);
        $dispatcher = new Dispatcher($podcast);
        $dispatcher->uncache_if_forced('secret', array('force' => 'wrong'));
    }

    public function test_correct_password_uncaches()
    {
        $podcast = $this->makePodcastMock(true);
        $dispatcher = new Dispatcher($podcast);
        $dispatcher->uncache_if_forced('secret', array('force' => 'secret'));
    }

    public function test_numeric_zero_password_does_not_match_arbitrary_string()
    {
        // parse_ini_file may yield an int for a value like "0". In PHP <8 a loose
        // comparison `0 == "anything"` is true for non-numeric strings, which
        // would let any input bypass the password check.
        $podcast = $this->makePodcastMock(false);
        $dispatcher = new Dispatcher($podcast);
        $dispatcher->uncache_if_forced(0, array('force' => 'anything'));
    }

    public function test_numeric_password_does_not_loose_match_prefix_string()
    {
        // `123 == "123abc"` is true in PHP <8 numeric coercion.
        $podcast = $this->makePodcastMock(false);
        $dispatcher = new Dispatcher($podcast);
        $dispatcher->uncache_if_forced(123, array('force' => '123abc'));
    }

    public function test_numeric_looking_string_password_does_not_collide_via_loose_equality()
    {
        // Two numeric-looking strings compared with == undergo numeric coercion in
        // every supported PHP version. So "01" == "1" is true under loose equality,
        // letting "?force=1" bypass FORCE_PASSWORD="01". Strict / hash_equals fixes this.
        $podcast = $this->makePodcastMock(false);
        $dispatcher = new Dispatcher($podcast);
        $dispatcher->uncache_if_forced('01', array('force' => '1'));
    }

    public function test_exponent_form_password_does_not_collide()
    {
        // "0e1234" == "0e5678" is true under == because both coerce to 0.0.
        $podcast = $this->makePodcastMock(false);
        $dispatcher = new Dispatcher($podcast);
        $dispatcher->uncache_if_forced('0e1234', array('force' => '0e5678'));
    }
}
