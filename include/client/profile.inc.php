<div class="row">
<h1><?php echo __('Manage Your Profile Information'); ?></h1>
<p><?php echo __(
'Use the forms below to update the information we have on file for your account'
); ?>
</p>
</div>
<div class="row">
<form class="form-horizontal" action="profile.php" method="post">
  <?php csrf_token(); ?>
<?php
foreach ($user->getForms() as $f) {
    $f->render(false);
}
if ($acct = $thisclient->getAccount()) {
    $info=$acct->getInfo();
    $info=Format::htmlchars(($errors && $_POST)?$_POST:$info);
?>
<hr>
        <h3><?php echo __('Preferences'); ?></h3>
        <div class="form-group">
        <label class="control-label col-sm-3" for="tz"><?php echo __('Time Zone');?>:</label>
        <div class="col-sm-9"><p id="tz" class="form-control-static"><?php
            $TZ_NAME = 'timezone';
            $TZ_TIMEZONE = $info['timezone'];
            include INCLUDE_DIR.'staff/templates/timezone.tmpl.php'; ?>
            </p>
            <div class="error"><?php echo $errors['timezone']; ?></div>
            </div>
        </div>
<?php if ($cfg->getSecondaryLanguages()) { ?>
    <div class="form-group">
        <label class="control-label col-sm-3">
            <?php echo __('Preferred Language'); ?>:
        </label>
        <div class="col-sm-9">
    <?php
    $langs = Internationalization::getConfiguredSystemLanguages(); ?>
            <select class="form-control" name="lang">
                <option value="">&mdash; <?php echo __('Use Browser Preference'); ?> &mdash;</option>
<?php foreach($langs as $l) {
$selected = ($info['lang'] == $l['code']) ? 'selected="selected"' : ''; ?>
                <option value="<?php echo $l['code']; ?>" <?php echo $selected;
                    ?>><?php echo Internationalization::getLanguageDescription($l['code']); ?></option>
<?php } ?>
            </select>
            <span class="error">&nbsp;<?php echo $errors['lang']; ?></span>
        </div>
    </div>
<?php }
      if ($acct->isPasswdResetEnabled()) { ?>
      <hr>
<div class="form-header">
    <h3><?php echo __('Access Credentials'); ?></h3>
    </div>
<?php if (!isset($_SESSION['_client']['reset-token'])) { ?>
<div class="form-group">
    <label class="control-label col-sm-3">
        <?php echo __('Current Password'); ?>:
    </label>
    <div class="col-sm-6">
        <input type="password" class="form-control" size="18" name="cpasswd" value="<?php echo $info['cpasswd']; ?>">
        &nbsp;<span class="error">&nbsp;<?php echo $errors['cpasswd']; ?></span>
    </div>
</div>
<?php } ?>
<div class="form-group">
    <label class="control-label col-sm-3">
        <?php echo __('New Password'); ?>:
    </label>
    <div class="col-sm-6">
        <input type="password" class="form-control" size="18" name="passwd1" value="<?php echo $info['passwd1']; ?>">
        &nbsp;<span class="error">&nbsp;<?php echo $errors['passwd1']; ?></span>
    </div>
</div>
<div class="form-group">
    <label class="control-label col-sm-3">
        <?php echo __('Confirm New Password'); ?>:
    </label>
    <div class="col-sm-6">
        <input type="password" class="form-control" size="18" name="passwd2" value="<?php echo $info['passwd2']; ?>">
        &nbsp;<span class="error">&nbsp;<?php echo $errors['passwd2']; ?></span>
    </div>
</div>
<?php } ?>
<?php } ?>
<hr>
<p class="buttons text-center">
    <input type="submit" class="btn btn-success" value="Update"/>
    <input type="reset" class="btn btn-warning" value="Reset"/>
    <input type="button" class="btn btn-default" value="Cancel" onclick="javascript:
        window.location.href='index.php';"/>
</p>
</form>
</div>
