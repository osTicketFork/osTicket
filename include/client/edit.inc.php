<?php

if(!defined('OSTCLIENTINC') || !$thisclient || !$ticket || !$ticket->checkUserAccess($thisclient)) die('Access Denied!');

?>
<div class="row">
<h1>
    <?php echo sprintf(__('Editing Ticket #%s'), $ticket->getNumber()); ?>
</h1>
<form action="tickets.php" class="form-horizontal" method="post">
    <?php echo csrf_token(); ?>
    <input type="hidden" name="a" value="edit"/>
    <input type="hidden" name="id" value="<?php echo Format::htmlchars($_REQUEST['id']); ?>"/>
    <div id="dynamic-form">
        <?php foreach ($forms as $form) {
            include(CLIENTINC_DIR . 'templates/dynamic-form.tmpl.php');
        } ?>
    </div>
<p>
    <input class="btn btn-success" type="submit" value="Update"/>
    <input class="btn btn-warning" type="reset" value="Reset"/>
    <input class="btn btn-default" type="button" value="Cancel" onclick="history.go(-1);"/>
</p>
</form>
</div>
