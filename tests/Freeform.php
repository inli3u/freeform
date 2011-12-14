<?php

require_once('PHPUnit/Autoload.php');
require_once('src/freeform.php');

class FreeformTest extends PHPUnit_Framework_TestCase
{

    public function testListErrors()
    {
        $form = new Freeform();
        $this->assertCount(0, $form->listErrors());

        $form->name = new FText();
        $this->assertCount(0, $form->listErrors());

        $msg = 'Typical validation error';
        $form->name->setError($msg);
        $list = $form->listErrors();
        $this->assertCount(1, $list);
        $this->assertEquals($list[0], $msg);

        $form->name->setError('');
        $this->assertCount(0, $form->listErrors());
    }

    public function testHasErrors()
    {
        $form = new Freeform();
        $form->name = new FText();
        $this->assertFalse($form->hasErrors());

        $form->name->setError('All out of pudding');
        $this->assertTrue($form->hasErrors());

        $form->name->setError('');
        $this->assertFalse($form->hasErrors());
    }

    public function testSetValidate()
    {
        $form = new Freeform();
        $form->name = new FText(null, 'required');
        $this->assertTrue($this->hasErrors());
        $form->name->value = 'Bob';
        $this->assertFalse($this->hasErrors());
    }
}

