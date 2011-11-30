<?php

require_once('../src/freeform.php');

$classes = array(
    'TextInput',
    'RadioInput',
    'CheckboxInput',
    'HiddenInput',
    'PasswordInput',
    'SubmitInput',
    'NumberInput',
    'RangeInput',
    'ColorInput',
    'FileInput',
    'ResetInput',
    'ButtonInput',
    'ImageInput',
    'TextareaInput',
    'SelectInput',
);

$examples = array();
foreach ($classes as $class) {
    $examples[] = array(
        'name' => $class,
        'instance' => new $class('value="Preview"')
    );
}

?>

<style type="text/css">
body { font-family: sans-serif; }
ul, li { margin: 0; padding: 0; }
li { list-style-type: none; }
label { display: block; margin: 1em 0; font-size: 20px; }
div.code { display: inline-block; width: 500px; }
div.preview {display: inline-block; }
</style>

<h1>Freeform Inputs</h1>

<ul>
    <?php foreach ($examples as $example): ?>
    <li>
        <label><?php echo $example['name']; ?></label>
        <div class="code"><?php echo htmlspecialchars($example['instance']); ?></div>
        <div class="preview"><?php echo $example['instance']; ?></div>
    </li>
    <?php endforeach; ?>
</ul>

