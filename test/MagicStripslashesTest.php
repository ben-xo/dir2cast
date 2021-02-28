<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class MagicStripslashesTest extends TestCase
{
	public function test_magic_striplashes()
	{
		$this->assertEquals('""\'\'', magic_stripslashes('""\'\''));
	}
}
