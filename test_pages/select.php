<?php

require_once('../src/freeform.php');

$s = new FSelect();
$s->add(array(1, 'First'));
$s->add(array(2, 'Second'));
$s->add(array(3, 'Third'));
echo $s;

echo '<p>Length: ' . $s->length . '</p>';
echo '<hr>';

$s->value = '2';
echo '<p>Value: ' . $s->value. '</p>';
echo '<p>Selected: ' . $s->selectedIndex. '</p>';
echo $s;

$s->selectedIndex = 2;
echo '<p>Value: ' . $s->value. '</p>';
echo '<p>Selected: ' . $s->selectedIndex. '</p>';
echo $s;
