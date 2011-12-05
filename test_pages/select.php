<?php

require_once('../src/freeform.php');

$s = new FSelect();
$s->add(new FOption('First', 1));
$s->add(new FOption('Second', 2));
$s->add(new FOption('Third', 3));
echo $s;
echo '<p>Length: ' . $s->length . '</p>';
echo '<hr>';

$s->disabled = true;
$s->value = '2';
echo '<p>Value: ' . $s->value. '</p>';
echo '<p>Selected: ' . $s->selectedIndex. '</p>';
echo $s;
echo '<hr>';

$s->selectedIndex = 2;
echo '<p>Value: ' . $s->value. '</p>';
echo '<p>Selected: ' . $s->selectedIndex. '</p>';
echo $s;
echo '<hr>';
$s->length = 1;
echo $s;
$s->length = 0;
echo $s;

/*
$s->add(new FOption(1, 'First'));
$s->remove($index);
$s->clear();
$s->fill($rows, 'valuekey', 'textkey');
foreach ($s->listOptions() as $option) {
    $option->text;
    $option->value;
}
$s->option($index);
$s->option($index, new FOption())
 */
