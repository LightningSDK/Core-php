<form method="post">
    <?= \Lightning\Tools\Form::renderTokenInput(); ?>
    <?= \Lightning\View\JSONEditor::render('jsoneditor', $jsoneditor->getSettings(), $jsoneditor->getJSONData()); ?>
    <input class="button" type="submit" name="submit" value="Save" />
</form>
