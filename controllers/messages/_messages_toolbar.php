<div data-control="toolbar" class="loading-indicator-container">
    <a
        href="javascript:;"
        data-request="onClearCache"
        data-load-indicator="<?= e(trans('rainlab.translate::lang.messages.clear_cache_loading')) ?>"
        class="btn btn-primary oc-icon-check-square"><?= e(trans('rainlab.translate::lang.messages.clear_cache_link')) ?>
    </a>
    <a
        href="javascript:;"
        data-control="popup"
        data-handler="onLoadScanMessagesForm"
        class="btn btn-default oc-icon-refresh"><?= e(trans('rainlab.translate::lang.messages.scan_messages_link')) ?>
    </a>
    <a
        href="<?= Backend::url('rainlab/translate/messages/import') ?>"
        class="btn btn-default oc-icon-sign-in">
        <?= e(trans('rainlab.translate::lang.messages.import_messages_link')) ?>
    </a>
    <a
        href="<?= Backend::url('rainlab/translate/messages/export') ?>"
        class="btn btn-default oc-icon-sign-out">
        <?= e(trans('rainlab.translate::lang.messages.export_messages_link')) ?>
    </a>
</div>
