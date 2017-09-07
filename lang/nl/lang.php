<?php

return [
    'plugin' => [
        'name' => 'Vertaal',
        'description' => 'Stelt meerdere talen in voor een website.',
        'manage_locales' => 'Manage locales',
        'manage_messages' => 'Manage messages'
    ],
    'locale_picker' => [
        'component_name' => 'Taalkeuze menu',
        'component_description' => 'Weergeeft een taal keuzemenu om de taal te wijzigen voor de website.',
    ],
    'locale' => [
        'title' => 'Beheer talen',
        'update_title' => 'Wijzig taal',
        'create_title' => 'Taal toevoegen',
        'select_label' => 'Selecteer taal',
        'default_suffix' => 'standaard',
        'unset_default' => '":locale" is al de standaard taal en kan niet worden uitgeschakeld',
        'disabled_default' => '":locale" is uitgeschakeld en kan niet worden gezet als standaard taal.',
        'name' => 'Naam',
        'code' => 'Code',
        'is_default' => 'Standaard',
        'is_default_help' => 'De standaard taal weergeeft de inhoud voor de vertaling.',
        'is_enabled' => 'Geactiveerd',
        'is_enabled_help' => 'Uitgeschakelde talen zijn niet beschikbaar op de website.',
        'not_available_help' => 'Er zijn geen andere talen beschikbaar.',
        'hint_locales' => 'Voeg hier nieuwe talen toe voor het vertalen van de website inhoud. De standaard taal weergeeft de inhoud voordat het is vertaald. ',
    ],
    'messages' => [
        'title' => 'Vertaal berichten',
		'description' => 'Wijzig berichten',
        'clear_cache_link' => 'Leeg cache',
        'clear_cache_loading' => 'Applicatie cache legen...',
        'clear_cache_success' => 'De applicatie cache is succesvol geleegd.',
        'clear_cache_hint' => 'Het is verstandig om regelmatig op <strong>Leeg cache</strong> te klikken om de veranderingen te zien op de website.',
        'scan_messages_link' => 'Zoek naar nieuwe berichten',
        'scan_messages_loading' => 'Zoeken naar nieuwe berichten...',
        'scan_messages_success' => 'De thema bestanden zijn succesvol gescand!',
        'scan_messages_hint' => 'Klikken op <strong>Zoeken naar nieuwe berichten</strong> controleert de actieve thema bestanden voor nieuwe berichten om te vertalen. ',
        'hint_translate' => 'Hier kan je berichten vertalen die worden gebruikt op de website. De velden worden automatisch opgeslagen.',
        'hide_translated' => 'Verberg vertaalde berichten',
    ],
];