<?php

require_once('src/freeform.php');


/* Introduction */

// New text input with its HTML size attribute set to 5 and the validation rule "required."
$hello = new Freeform::Field('text', 'size=5', 'required');

// Our input field has no value yet, so "This field is required."
echo $hello->get_error() . "<br />\n";

// HTML attributes can be modified with ease after the field is created.
$hello->value = 'world';

if ($hello->is_valid()) {
    // This is now true.
}

// Doing this kind of thing inline with the HTML tag is a pain.
$hello->disabled = true;

$hello->render();
// Produces: <input type="text" size="5" value="world" />


/* Forms */

$form = new Freeform::Form($_POST);
$form->hello = $hello;

// Something special just happened. When a field is assigned to a form, the field receives an id and name