<?php

require_once('src/freeform.php');

// Form definition.
class ExampleForm extends Freeform::Form
{
    public function config()
    {
        // Notice the initial HTML attributes being set.
        $this->name = new Field('text', 'class=standard-width', 'required');
        $this->email = new Field('text', 'class="class1 class2" size=3', 'required email');
        // Validation rules can accept arguments.
        $this->postal_code = new Field('text', null, 'length=5');
    }
}

// Create the form, setting the initial values for each field to what the user entered last.
$form = new ExampleForm($_POST);

if (count($_POST)) {
    // Check the supplied validations rules against the current values
    $form->validate();
}

// Disable the name field, just for kicks, but also to show how HTML attributes can be modified programatically.
$form->name->disabled = true;

// Display each form field individually below.
?>

<form action="#" method="post">
    <ul>
        <li>
            <label for="name">Name:</label>
            <?php echo $form->name->render(); ?>
        </li>
        <li>
            <label for="email">E-mail address:</label>
            <?php echo $form->email->render(); ?>
        </li>
        <li>
            <label for="postal_code">Postal Code:</label>
            <?php echo $form->postal_code->render(); ?>
        </li>
    </ul>
    <input type="submit" />
</form>
