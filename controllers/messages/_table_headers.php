<!-- From Header -->
<script type="text/template" id="<?= $this->getId('fromTitle') ?>">
    <div class="header-language">
        <?= e($defaultLocale->name) ?> <span class="is-default">- <?= __("default") ?></span>
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
        <label class="storm-icon-pseudo" for="hideTranslated"><?= __("Hide Translated") ?></label>
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
            <li class="no-other-languages text-muted"><small><?= __("There are no other languages set up.") ?></small></li>
        <?php endif ?>
    </ul>
</div>
