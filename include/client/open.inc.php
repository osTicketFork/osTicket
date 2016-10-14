<?php
if(!defined('OSTCLIENTINC')) die('Access Denied!');
$info=array();
if($thisclient && $thisclient->isValid()) {
    $info=array('name'=>$thisclient->getName(),
                'email'=>$thisclient->getEmail(),
                'phone'=>$thisclient->getPhoneNumber());
}

$info=($_POST && $errors)?Format::htmlchars($_POST):$info;

$form = null;
if (!$info['topicId']) {
    if (array_key_exists('topicId',$_GET) && preg_match('/^\d+$/',$_GET['topicId']) && Topic::lookup($_GET['topicId']))
        $info['topicId'] = intval($_GET['topicId']);
    else
        $info['topicId'] = $cfg->getDefaultTopicId();
}
$forms = array();
if ($info['topicId'] && ($topic=Topic::lookup($info['topicId']))) {
    foreach ($topic->getForms() as $F) {
        if (!$F->hasAnyVisibleFields())
            continue;
        if ($_POST) {
            $F = $F->instanciate();
            $F->isValidForClient();
        }
        $forms[] = $F;
    }
}

?>
<div class="row">
<div class="page-title">
<h1><?php echo __('Open a New Ticket');?></h1>
<p><?php echo __('Please fill in the form below to open a new ticket.');?></p>
</div>
</div>
<div class="row">
<form id="ticketForm" class="form-horizontal" method="post" action="open.php" enctype="multipart/form-data">
  <?php csrf_token(); ?>
  <input type="hidden" name="a" value="open">
<?php
        if (!$thisclient) {
            $uform = UserForm::getUserForm()->getForm($_POST);
            if ($_POST) $uform->isValid();
            $uform->render(false);
        }
        else { ?>
        <hr>
        <div class="form-group">
            <label class="control-label col-sm-2" for="email"><?php echo __('Email'); ?>:</label>
            <div  class="col-sm-10"><p id="email" class="form-control-static"><?php echo $thisclient->getEmail(); ?></p></div>
        </div>
        <div class="form-group">
            <label class="control-label col-sm-2" for="name"><?php echo __('Client'); ?>:</label>
            <div  class="col-sm-10"><p id="name" class="form-control-static"><?php echo $thisclient->getName(); ?></p></div>
        </div>
        <?php } ?>
        <hr>
        <div class="form-group">
        <label class="control-label col-sm-2 required" for="topicId"><?php echo __('Help Topic'); ?>
            <font class="error">*</font></label>
            <div  class="col-sm-10">
            <select class="form-control" id="topicId" name="topicId" onchange="javascript:
                    var data = $(':input[name]', '#dynamic-form').serialize();
                    $.ajax(
                      'ajax.php/form/help-topic/' + this.value,
                      {
                        data: data,
                        dataType: 'json',
                        success: function(json) {
                          $('#dynamic-form').empty().append(json.html);
                          $(document.head).append(json.media);
                        }
                      });">
                <option value="" selected="selected">&mdash; <?php echo __('Select a Help Topic');?> &mdash;</option>
                <?php
                if($topics=Topic::getPublicHelpTopics()) {
                    foreach($topics as $id =>$name) {
                        echo sprintf('<option value="%d" %s>%s</option>',
                                $id, ($info['topicId']==$id)?'selected="selected"':'', $name);
                    }
                } else { ?>
                    <option value="0" ><?php echo __('General Inquiry');?></option>
                <?php
                } ?>
            </select>
            <div class="alert-danger"><?php echo $errors['topicId']; ?></div>
            </div>
        </div>
    <div id="dynamic-form">
        <?php foreach ($forms as $form) {
            include(CLIENTINC_DIR . 'templates/dynamic-form.tmpl.php');
        } ?>
    </div>
    <?php
    if($cfg && $cfg->isCaptchaEnabled() && (!$thisclient || !$thisclient->isValid())) {
        if($_POST && $errors && !$errors['captcha'])
            $errors['captcha']=__('Please re-enter the text again');
        ?>
    <hr>
    <div class="form-group">
        <label class="control-label col-sm-2 required"> <?php echo __('CAPTCHA Text');?>
            <font class="error">*</font></label>
        <div class="col-sm-10 row">
            <div class="col-sm-2 captchaRow">
                <span class="captcha"><img src="captcha.php" border="0" align="left"></span>
            </div>
            <div class="col-sm-2">
                <input id="captcha" class="form-control col-sm-2" type="text" name="captcha" size="6" autocomplete="off">
            </div>
                <?php if (!isset($errors['captcha'])) {
                ?><span class="help-block"><?php echo __('Enter the text shown on the image.');?></span>
                <?php } else {
                ?><span class="alert-danger"><?php echo $errors['captcha']; ?></span>
                <?php }
                ?>
        </div>
    </div>
    <?php
    } ?>
   <hr/>
   <p class="buttons">
        <input class="btn btn-success" type="submit" value="<?php echo __('Create Ticket');?>">
        <input class="btn btn-warning" type="reset" name="reset" value="<?php echo __('Reset');?>"onclick="javascript:window.location.href='open.php';">
        <input class="btn btn-default" type="button" name="cancel" value="<?php echo __('Cancel'); ?>" onclick="javascript:
  </p>
</form>
</div>
<div class="clearfix"></div>
