<!-- From Header -->
<script type="text/template" id="<?= $this->getId('fromTitle') ?>">
    <div class="header-language">
        <?= e($defaultLocale->name) ?> <span class="is-default">- <?= e(trans('rainlab.translate::lang.locale.default_suffix')) ?></span>
    </div>
</script>

<!-- To Header -->
<script type="text/template" id="<?= $this->getId('toTitle') ?>">
    <div class="header-language" onclick="$.translateMessages.toggleDropdown('#toTitleButton')">
        <?= e($selectedTo->name) ?>
        <i class="icon-angle-down"></i>
    </div>
    <div class="header-hide-translated custom-checkbox">
        <input type="checkbox" id="hideTranslated" name="hide_translated" value="1" {{#hideTranslated}}checked{{/hideTranslated}} />
        <label class="storm-icon-pseudo" for="hideTranslated"><?= e(trans('rainlab.translate::lang.messages.hide_translated')) ?></label>
    </div>
</script>

<!-- To Language Picker -->
<div class="dropdown dropdown-to">
    <span data-toggle="dropdown" id="toTitleButton" class="dropdown-button-placeholder"></span>
    <ul class="dropdown-menu" role="menu" data-dropdown-title="Language">
        <?php foreach ($locales as $locale): ?>
            <li role="presentation">
                <a
                    role="menuitem"
                    tabindex="-1"
                    href="#"
                    onclick="return $.translateMessages.setLanguage('<?= $locale->code ?>')">
                    <?= $locale->name ?>
                </a>
            </li>
        <?php endforeach ?>
        <?php if (count($locales) <= 1): ?>
            <li class="no-other-languages text-muted"><small><?= e(trans('rainlab.translate::lang.locale.not_available_help')) ?></small></li>
        <?php endif ?>
    </ul>
</div>

<!-- Found Header -->
<script type="text/template" id="<?= $this->getId('foundTitle') ?>">
    <?= e(trans('rainlab.translate::lang.messages.found_title')) ?> <i class="icon-info-circle" style="cursor: help" title="<?= e(trans('rainlab.translate::lang.messages.found_help')) ?>"></i>
</script>
