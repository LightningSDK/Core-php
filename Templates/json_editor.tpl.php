<form method="post">
    <?= \lightningsdk\core\Tools\Form::renderTokenInput(); ?>
    <?= \lightningsdk\core\View\JSONEditor::render('jsoneditor', $jsoneditor->getSettings(), $jsoneditor->getJSONData()); ?>
    <input class="button" type="submit" name="submit" value="Save" />
</form>
