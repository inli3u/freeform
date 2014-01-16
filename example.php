<?php

require_once('src/freeform.php');

// Form definition.
class ExampleForm extends Freeform
{
    public function config()
    {
        // Notice the initial HTML attributes being set.
        $this->name = new FText(['class' => 'standard-width'], 'required');
        
        $this->email = new FText(['class' => 'class1 class2', 'size' => 3], 'required email');
        // Validation rules can accept arguments.
        $this->postalCode = new FText(null, 'length=5');
    }
}

// Create the form, setting the initial values for each field to what the user entered last.
$form = new ExampleForm($_POST);

if (count($_POST)) {
    // Check the supplied validations rules against the current values
    $errors = $form->listErrors();
}

// Disable the name field, just for kicks, but also to show how HTML attributes can be modified programatically.
$form->name->disabled = true;

// Display each form field individually below.
?>

<style type="text/css">
ul, li {
	margin: 0;
	padding: 0;
}
body {
	font-family: arial, sans-serif;
}
li {
	list-style-type: none;
	margin: 0.5em 0;
}
label {
	display: inline-block;
	width: 8em;
}
</style>

<h1>Freeform example</h1>
<form action="#" method="post">
    <ul>
        <li>
            <label for="name">Name:</label>
            <?php echo $form->name->render(); ?>
            <?php echo $form->name->getError(); ?>
        </li>
        <li>
            <label for="email">E-mail address:</label>
            <?php echo $form->email->render(); ?>
            <?php echo $form->email->getError(); ?>
        </li>
        <li>
            <label for="postal_code">Postal Code:</label>
            <?php echo $form->postalCode->render(); ?>
            <?php echo $form->postalCode->getError(); ?>
        </li>
    </ul>
    <input type="submit" />
</form>
