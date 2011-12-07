<?php

require_once('PHPUnit/Autoload.php');
require_once('src/freeform.php');


class FValidateTest extends PHPUnit_Framework_TestCase
{
    protected $validate;

    public function setUp()
    {
        $this->validate = new FValidate();
    }

    public function testRequired()
    {
        $this->assertTrue($this->validate->required('test'));
        $this->assertTrue($this->validate->required('0'));
        $this->assertFalse($this->validate->required(''));
    }

    public function testMinlength()
    {
    }

    public function testMaxlength()
    {
    }

    public function testMin()
    {
    }

    public function testMax()
    {
    }
}

/* vim: set ts=4 sw=4 et */
