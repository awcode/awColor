<?php

namespace Test\AWcode;

require_once 'PHPUnit/Autoload.php';
use PHPUnit\Framework\TestCase;

class awColorTest extends TestCase
{
    public function testSetGetHex()
    {
        $color = new \AWcode\awColor('#ff0000');
        $this->assertEquals('ff0000', $color->getHex());
    }
}
