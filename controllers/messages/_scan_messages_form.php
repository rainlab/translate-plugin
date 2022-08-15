<div id="scanMessagesPopup">
    <?= Form::open(['id' => 'scanMessagesForm']) ?>
        <div class="modal-header flex-row-reverse">
            <button type="button" class="close" data-dismiss="popup">&times;</button>
            <h4 class="modal-title"><?= __("Scan for Messages") ?></h4>
        </div>

        <div class="modal-body">
            <p>
                <?= __("This process will attempt to scan the active theme for messages that can be translated.") ?>
                <?= __("Some messages may not be captured and will only appear after the first time they are used.") ?>

            </p>
            <div class="form-preview">
                <div class="form-group">
                    <!-- Checkbox -->
                    <div class="checkbox custom-checkbox">
                        <input
                            type="checkbox"
                            name="purge_messages"
                            value="1"
                            id="purgeMessages" />
                        <label for="purgeMessages" class="storm-icon-pseudo">
                            <?= __("Purge all messages first") ?>
                        </label>
                        <p class="help-block form-text">
                            <?= __("If checked, this will delete all messages, including their translations, before performing the scan.") ?>
                        </p>
                    </div>

                    <div class="checkbox custom-checkbox">
                        <input
                            type="checkbox"
                            name="purge_deleted_messages"
                            value="1"
                            id="purgeDeletedMessages">
                        <label for="purgeDeletedMessages" class="storm-icon-pseudo">
                            <?= __("Purge missing messages after scan") ?>
                        </label>
                        <p class="help-block form-text">
                            <?= __("If checked, after the scan is done, any messages the scanner did not find, including their translations, will be deleted. This cannot be undone!") ?>
                        </p>
                    </div>
                </div>
            </div>

        </div>
        <div class="modal-footer">
            <div class="loading-indicator-container">
                <button
                    type="submit"
                    class="btn btn-success"
                    data-request="onScanMessages"
                    data-load-indicator="<?= __("Scanning for new messages...") ?>"
                    data-request-success="$(this).trigger('close.oc.popup')"
                    id="scanMessagesButton">
                    <?= __("Begin Scan") ?>
                </button>
                <button
                    type="button"
                    class="btn btn-default"
                    data-dismiss="popup">
                    <?= e(trans('backend::lang.form.cancel')) ?>
                </button>
            </div>
        </div>

    <?= Form::close() ?>
</div>

<script>
    $('#purgeMessages').on('change', function() {
        if ($(this).is(':checked')) {
            $('#scanMessagesButton').data('request-confirm', '<?= e(__("Are you sure you want to delete all messages? This cannot be undone!")) ?>')
        }
        else {
            $('#scanMessagesButton').removeData('request-confirm')
        }
    })
</script>
