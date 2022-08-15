<?php Block::put('breadcrumb') ?>
    <ul>
        <li><a href="<?= Backend::url('system/settings') ?>"><?= __("Settings") ?></a></li>
        <li><a href="<?= Backend::url('rainlab/translate/messages') ?>"><?= __("Translate Messages") ?></a></li>
        <li><?= e(trans($this->pageTitle)) ?></li>
    </ul>
<?php Block::endPut() ?>

<?= Form::open(['class' => 'layout']) ?>

    <div class="layout-row">
        <?= $this->exportRender() ?>
    </div>

    <div class="form-buttons">
        <button
            type="submit"
            data-control="popup"
            data-handler="onExportLoadForm"
            data-keyboard="false"
            class="btn btn-primary">
            <?= __("Export Messages") ?>
        </button>
    </div>

<?= Form::close() ?>
