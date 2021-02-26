<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class MyGetterSetter extends GetterSetter { }

final class GetterSetterTest extends TestCase
{

    public function test_getter_setter_magic_methods()
    {
        $mgc = new MyGetterSetter();
        $mgc->setJunk('a');
        $mgc->setOtherJunk('b');

        $this->assertEquals('a', $mgc->getJunk());
        $this->assertEquals('b', $mgc->getOtherJunk());
    }

}
