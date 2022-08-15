<div data-control="toolbar" class="loading-indicator-container">
    <a
        href="javascript:;"
        data-control="popup"
        data-handler="onLoadScanMessagesForm"
        class="btn btn-primary oc-icon-refresh"><?= __("Scan for Messages") ?>
    </a>
    <a
        href="javascript:;"
        data-request="onClearCache"
        data-load-indicator="<?= __("Clearing application cache...") ?>"
        class="btn btn-link oc-icon-check-square"><?= __("Clear Cache") ?>
    </a>
    <a
        href="<?= Backend::url('rainlab/translate/messages/import') ?>"
        class="btn btn-link oc-icon-sign-in">
        <?= __("Import Messages") ?>
    </a>
    <a
        href="<?= Backend::url('rainlab/translate/messages/export') ?>"
        class="btn btn-link oc-icon-sign-out">
        <?= __("Export Messages") ?>
    </a>
</div>
