<?php

return [
    'plugin' => [
        'name' => 'Fordítás',
        'description' => 'Többnyelvűvé teszi a weboldalt.',
        'tab' => 'Fordítás',
        'manage_locales' => 'Nyelvek kezelése',
        'manage_messages' => 'Szövegek fordítása'
    ],
    'locale_picker' => [
        'component_name' => 'Nyelvválasztó',
        'component_description' => 'Legördülő menüt jelenít meg a nyelv kiválasztásához.'
    ],
    'locale' => [
        'title' => 'Nyelvek',
        'update_title' => 'Nyelv frissítése',
        'create_title' => 'Nyelv hozzáadása',
        'select_label' => 'Nyelv választása',
        'default_suffix' => 'alapértelmezett',
        'unset_default' => 'Már a(z) ":locale" nyelv az alapértelmezett, így nem használható alapértelmezettként.',
        'disabled_default' => 'A(z) ":locale" nyelv letiltott, így nem állítható be alapértelmezettként.',
        'name' => 'Név',
        'code' => 'Kód',
        'is_default' => 'Alapértelmezett',
        'is_default_help' => 'Az alapértelmezett nyelv a fordítás előtti tartalmat képviseli.',
        'is_enabled' => 'Engedélyezve',
        'is_enabled_help' => 'A letiltott nyelvek nem lesznek elérhetőek a felhasználói oldalon.',
        'not_available_help' => 'Nincsenek más beállított nyelvek.',
        'hint_locales' => 'Itt hozhat létre új nyelveket a felhasználói oldal tartalmának lefordításához. Az alapértelmezett nyelv képviseli a fordítás előtti tartalmat.'
    ],
    'messages' => [
        'title' => 'Szövegek',
        'description' => 'Nyelvi változatok menedzselése.',
        'clear_cache_link' => 'Gyorsítótár kiürítése',
        'clear_cache_loading' => 'Az alkalmazás gyorsítótár kiürítése...',
        'clear_cache_success' => 'Sikerült az alkalmazás gyorsítótár kiürítése!',
        'clear_cache_hint' => 'Böngészőtől függően lehet, hogy a <strong>Gyorsítótár kiürítése</strong> gombra kell kattintania, hogy a módosítások láthatók legyenek a felhasználói oldalon is.',
        'scan_messages_link' => 'Szöveg keresése',
        'scan_messages_loading' => 'Új szöveg keresése...',
        'scan_messages_success' => 'Sikerült a keresés a téma fájljaiban!',
        'scan_messages_hint' => 'A <strong>Szöveg keresése</strong> gombra kattintva pedig megkeresheti a lefordítandó üzeneteket az aktív téma fájljaiban.',
        'hint_translate' => 'Itt fordíthatja le a felhasználói oldalon használt üzeneteket. A változtatások automatikusan mentésre kerülnek.',
        'hide_translated' => 'Lefordítottak elrejtése'
    ]
];
