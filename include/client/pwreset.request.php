<?php
if(!defined('OSTCLIENTINC')) die('Access Denied');

$userid=Format::input($_POST['userid']);
?>
<h1><?php echo __('Forgot My Password'); ?></h1>
<p><?php echo __(
'Enter your username or email address in the form below and press the <strong>Send Email</strong> button to have a password reset link sent to your email account on file.');
?>

    <p><strong><?php echo Format::htmlchars($banner); ?></strong></p>
    <br>

<form  class="form-horizontal" action="pwreset.php" method="post" id="clientLogin">
    <?php csrf_token(); ?>
    <input type="hidden" name="do" value="sendmail"/>
    <div class="form-group">
        <label class="control-label col-sm-2" for="username"><?php echo __('Username'); ?>:</label>
        <div class="col-sm-10">
        <input id="username" type="text" class="form-control" name="userid" size="30" value="<?php echo $userid; ?>">
        </div>
    </div>
    <p class="buttons text-center">
        <input class="btn btn-default" type="submit" value="<?php echo __('Send Email'); ?>">
    </p>
</form>
