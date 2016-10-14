<?php
    // Form headline and deck with a horizontal divider above and an extra
    // space below.
    // XXX: Would be nice to handle the decoration with a CSS class
    ?> 
    <div class="form-header" style="margin-bottom:0.5em">
    <h3><?php echo Format::htmlchars($form->getTitle()); ?></h3>
    <div><?php echo Format::display($form->getInstructions()); ?></div>
    </div>
    <?php
    // Form fields, each with corresponding errors follows. Fields marked 
    // 'private' are not included in the output for clients
    global $thisclient;
    foreach ($form->getFields() as $field) {
        if (isset($options['mode']) && $options['mode'] == 'create') {
            if (!$field->isVisibleToUsers() && !$field->isRequiredForUsers())
                continue;
        }
        elseif (!$field->isVisibleToUsers() && !$field->isEditableToUsers()) {
            continue;
        }
        ?>
        <div class="form-group">
            <?php if (!$field->isBlockLevel()) { ?>
                <label for="<?php echo $field->getFormName(); ?>" class="control-label col-sm-2 <?php if ($field->isRequiredForUsers()) echo 'required'; ?>">
                <?php echo Format::htmlchars($field->getLocal('label')); ?>
            <?php if ($field->isRequiredForUsers()) { ?>
                <span class="error">*</span>
            <?php }
            ?></label>
                <div  class="col-sm-10"><?php
                } else { ?>
                <div  class="col-sm-12"><?php 
                }
                $field->render(array('client'=>true));
                ?><?php
                foreach ($field->errors() as $e) { ?>
                    <div class="alert-danger"><?php echo $e; ?></div>
                <?php }
                $field->renderExtras(array('client'=>true));
                ?>
                <div class="<?php echo (@USE_BOOTSTRAP ? "help-block" : "dynamic-field-hint") ;?>"><?php
                    if ($field->get('hint')) { ?> <?php echo Format::viewableImages($field->getLocal('hint')); ?>
                    <?php
                    } ?>
                </div>
            </div>
        </div>
        <?php
    }
?>
