<?php

require_once('../src/freeform.php');

$form = new Freeform($_GET);
$form->user = new FText(null, 'required');
$form->pass = new FPassword(null, 'required minlength=6');
$form->passConfirm = new FPassword(null, 'required');
$form->submit = new FSubmit();

if ($form->isSubmitted()) {
    if ($form->hasErrors()) {
        print_r($form->listErrors());
    }
    die('submitted');
}

?>

<form action="form.php" method="get">
    <p>Username: <?php echo $form->user; ?></p>
    <p>Password: <?php echo $form->pass; ?></p>
    <p>Confirm: <?php echo $form->passConfirm; ?></p>
    <p><?php echo $form->submit; ?></p>
</form>
