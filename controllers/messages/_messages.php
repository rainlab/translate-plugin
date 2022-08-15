<div class="control-toolbar">
    <div class="toolbar-item toolbar-primary">
        <?= $this->makePartial('messages_toolbar') ?>
    </div>
</div>

<div class="translate-messages">
    <div id="tableHeaderContainer">
        <?= $this->makePartial('table_headers') ?>
    </div>
    <div id="tableToolbarContainer">
        <?= $this->makePartial('table_toolbar') ?>
    </div>

    <?= $table->render() ?>
</div>
