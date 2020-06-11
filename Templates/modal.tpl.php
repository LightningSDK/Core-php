<?php
use lightningsdk\core\Tools\Messenger;
use lightningsdk\core\View\JS;
?>
<div class="marketing off-canvas-wrap" data-offcanvas>
    <div class="inner-wrap">
        <?php if (Messenger::hasErrors() || Messenger::hasMessages()): ?>
            <div class="row">
                <div class="large-12 columns">
                    <?= Messenger::renderErrorsAndMessages(); ?>
                </div>
            </div>
        <?php endif; ?>
        <div class="row">
            <div class="medium-12 columns">
                <?php if (!empty($content)) :
                    $this->build($content);
                endif; ?>
            </div>
        </div>
    </div>
</div>
