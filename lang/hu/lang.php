<?php

return [
    'plugin' => [
        'name' => 'Fordítás',
        'description' => 'Többnyelvűvé teszi a webhelyeket.',
    ],
    'locale_picker' => [
        'component_name' => 'Nyelvválasztó',
        'component_description' => 'Legördülőt jelenít meg egy nyelv kiválasztásához a felhasználói oldalon.',
    ],
    'locale' => [
        'title' => 'Nyelvek kezelése',
        'update_title' => 'Nyelv frissítése',
        'create_title' => 'Nyelv létrehozása',
        'select_label' => 'Nyelv választása',
        'default_suffix' => 'alapértelmezett',
        'unset_default' => 'Már a(z) ":locale" nyelv az alapértelmezett, és nem használható alapértelmezettként.',
        'disabled_default' => 'A(z) ":locale" nyelv letiltott, és nem állítható be alapértelmezettként.',
        'name' => 'Név',
        'code' => 'Kód',
        'is_default' => 'Alapértelmezett',
        'is_default_help' => 'Az alapértelmezett nyelv a fordítás előtti tartalmat képviseli.',
        'is_enabled' => 'Engedélyezve',
        'is_enabled_help' => 'A letiltott nyelvek nem lesznek elérhetőek a felhasználói oldalon.',
        'not_available_help' => 'Nincsenek más beállított nyelvek.',
        'hint_locales' => 'Itt hozhat létre új nyelveket a felhasználói oldal tartalmának lefordításához. Az alapértelmezett nyelv képviseli a fordítás előtti tartalmat.',
    ],
    'messages' => [
        'title' => 'Üzenetek lefordítása',
        'clear_cache_link' => 'Gyorsítótár kiürítése',
        'clear_cache_loading' => 'Az alkalmazás-gyorsítótár kiürítése...',
        'clear_cache_success' => 'Sikerült az alkalmazás-gyorsítótár kiürítése!',
        'clear_cache_hint' => 'Lehet, hogy a <strong>Gyorsítótár kiürítése</strong> gombra kell kattintania, hogy a módosítások láthatók legyenek a felhasználói oldalon.',
        'scan_messages_link' => 'Üzenetek keresése',
        'scan_messages_loading' => 'Új üzenetek keresése...',
        'scan_messages_success' => 'Sikerült a keresés a téma sablonfájljaiban!',
        'scan_messages_hint' => 'Az <strong>Üzenetek keresése</strong> gombra kattintással megkeresheti a lefordítandó üzeneteket az aktív téma fájljaiban.',
        'hint_translate' => 'Itt fordíthatja le a felhasználói oldalon használt üzeneteket, a mezők automatikusan mentésre kerülnek.',
        'hide_translated' => 'Lefordítottak elrejtése',
    ],
];