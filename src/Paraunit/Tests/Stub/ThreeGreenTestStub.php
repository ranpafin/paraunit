<?php

namespace Paraunit\Tests\Stub;

/**
 * Class ThreeGreenTestStub
 * @package Paraunit\Tests\Stub
 */
class ThreeGreenTestStub extends \PHPUnit_Framework_TestCase
{
    public function testGreenOne()
    {
        $this->assertTrue(true);
    }

    public function testGreenTwo()
    {
        $this->assertTrue(true);
    }

    public function testGreenThree()
    {
        $this->assertTrue(true);
    }
}
