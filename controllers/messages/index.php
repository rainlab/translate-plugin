<?php Block::put('breadcrumb') ?>
    <ul>
        <li><a href="<?= Backend::url('system/settings') ?>"><?= __("Settings") ?></a></li>
        <li><?= e(trans($this->pageTitle)) ?></li>
    </ul>
<?php Block::endPut() ?>

<div class="callout-container" style="margin-bottom: -20px">
    <?= $this->makeHintPartial('translation_messages_hint', 'hint') ?>
</div>

<?= Form::open(['id' => 'messagesForm', 'class'=>'layout-item stretch layout-column', 'onsubmit'=>'return false']) ?>

    <div id="messagesContainer">
        <?= $this->makePartial('messages') ?>
    </div>

    <!-- Passable fields -->
    <input type="hidden" name="locale_to" value="<?= $selectedTo ? $selectedTo->code : '' ?>" />

<?= Form::close() ?>

<!-- Set the Header values in the Grid -->
<script>
    $(document).render(function(){
        $.translateMessages.setTableElement('#<?= $table->getId() ?>');
        $.translateMessages.setTitleContents('#<?= $this->getId('fromTitle') ?>', '#<?= $this->getId('toTitle') ?>', '#<?= $this->getId('toTitlePrune') ?>');
        $.translateMessages.setToolbarContents('#<?= $this->getId('tableToolbar') ?>');
    });
</script>
