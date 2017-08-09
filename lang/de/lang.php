<?php

return [
    'plugin'            => [
        'name'                  => 'Translate',
        'description'           => 'Ermöglicht mehrsprachige Seiten.',
        'manage_locales'        => 'Sprachen verwalten',
        'manage_messages'       => 'Übersetzungen verwalten'
    ],

    'locale_picker'     => [
        'component_name'        => 'Sprachauswahl',
        'component_description' => 'Zeigt ein Dropdown-Menü zur Auswahl der Sprache im Frontend.',
    ],

    'locale'            => [
        'title'                 => 'Sprachen verwalten',
        'update_title'          => 'Sprache bearbeiten',
        'create_title'          => 'Sprache erstellen',
        'select_label'          => 'Sprache auswählen',
        'default_suffix'        => 'Standard',
        'unset_default'         => '":locale" ist bereits die Standardsprache und kann nicht abgewählt werden.',
        'disabled_default'      => '":locale" ist deaktiviert und kann deshalb nicht als Standardsprache festgelegt werden.',
        'name'                  => 'Name',
        'code'                  => 'Code',
        'is_default'            => 'Standard',
        'is_default_help'       => 'Die Übersetzung der Standardsprache wird verwendet, um Inhalte anzuzeigen, die in der Sprache des Nutzers nicht vorhanden sind.',
        'is_enabled'            => 'Aktiv',
        'is_enabled_help'       => 'Deaktivierte Sprachen sind im Frontend nicht verfügbar.',
        'not_available_help'    => 'Es gibt keine anderen Sprachen.',
        'hint_locales'          => 'Hier können neue Sprachen angelegt werden, in die Inhalte im Frontend übersetzt werden können. Die Standardsprache dient als Ausgangssprache für Übersetzungen.',
        'reorder_title'         => 'Sprachen sortieren',
        'sort_order'            => 'Sortierung',
    ],

    'messages'          => [
        'title'                 => 'Übersetzungen verwalten',
        'description'           => 'Inhalte verwalten und übersetzen',
        'clear_cache_link'      => 'Cache leeren',
        'clear_cache_loading'   => 'Leere Application-Cache...',
        'clear_cache_success'   => 'Application-Cache erfolgreich geleert!',
        'clear_cache_hint'      => 'Möglicherweise muss der Cache geleert werden (Button <strong>Cache leeren</strong>), bevor Änderungen im Frontend sichtbar werden.',
        'scan_messages_link'    => 'Nach Inhalten suchen',
        'scan_messages_loading' => 'Suche nach neuen Inhalte...',
        'scan_messages_success' => 'Suche nach neuen Inhalte erfolgreich abgeschlossen!',
        'scan_messages_hint'    => 'Ein Klick auf <strong>Nach Inhalten suchen</strong> sucht nach neuen Inhalten, die übersetzt werden können.',
        'hint_translate'        => 'Hier können Inhalte aus dem Frontend übersetzt werden. Die Felder werden automatisch gespeichert.',
        'hide_translated'       => 'Bereits übersetzte Inhalte ausblenden',
    ],
];
