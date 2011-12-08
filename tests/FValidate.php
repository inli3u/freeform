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
        // Make sure falsy values count as values.
        $this->assertTrue($this->validate->required('0'));
        $this->assertFalse($this->validate->required(''));
    }

    public function testMinlength()
    {
        $this->assertTrue($this->validate->minlength('random', 0));
        $this->assertTrue($this->validate->minlength('four', 4));
        $this->assertFalse($this->validate->minlength('four', 5));
        $this->assertFalse($this->validate->minlength('', 1));
    }

    public function testMaxlength()
    {
        $this->assertTrue($this->validate->maxlength('', 0));
        $this->assertTrue($this->validate->maxlength('abcd', 4));
        $this->assertFalse($this->validate->maxlength('abcde', 4));
        $this->assertFalse($this->validate->maxlength('a', 0));
    }

    public function testMin()
    {
        $this->assertTrue($this->validate->min(1, 1));
        $this->assertTrue($this->validate->min(2, 1));
        $this->assertFalse($this->validate->min(2, 4));
    }

    public function testMax()
    {
        $this->assertTrue($this->validate->max(2, 2));
        $this->assertTrue($this->validate->max(1, 2));
        $this->assertFalse($this->validate->max(4, 2));
    }

    public function testPattern()
    {
        $pattern = '[0-9][A-Z]{3}';
        $this->assertTrue($this->validate->pattern('3ABC', $pattern));
        $this->assertFalse($this->validate->pattern('22FF', $pattern));
    }

    public function testEmail()
    {
        $this->assertTrue($this->validate->email('test@example.com'));
        $this->assertFalse($this->validate->email('incorrect'));
    }

    public function testUrl()
    {
    }

    public function testPhone()
    {
    }
}

/* vim: set ts=4 sw=4 et */
