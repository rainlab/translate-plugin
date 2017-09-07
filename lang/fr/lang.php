<?php

return [
    'plugin' => [
        'name' => 'Traductions',
        'description' => 'Permet de créer des sites Internet multilingues',
        'manage_locales' => 'Manage locales',
        'manage_messages' => 'Manage messages'
    ],
    'locale_picker' => [
        'component_name' => 'Sélection de la langue',
        'component_description' => 'Affiche un menu déroulant pour sélectionner la langue sur le site.',
    ],
    'locale' => [
        'title' => 'Gestion des langues',
        'update_title' => 'Mettre à jour la langue',
        'create_title' => 'Ajouter une langue',
        'select_label' => 'Sélectionner une langue',
        'default_suffix' => 'défaut',
        'unset_default' => '":locale" est déjà la langue par défaut et ne peut être désactivée',
        'disabled_default' => '":locale" est désactivé et ne peut être utilisé comme paramètre par défaut.',
        'name' => 'Nom',
        'code' => 'Code',
        'is_default' => 'Défaut',
        'is_default_help' => 'La langue par défaut représente le contenu avant la traduction.',
        'is_enabled' => 'Activer',
        'is_enabled_help' => 'Les langues désactivées ne seront plus disponibles sur le site.',
        'not_available_help' => 'Aucune autre langue n\'est définie.',
        'hint_locales' => 'Vous pouvez ajouter de nouvelles langues et traduire les messages du site. La langue par défaut est celle utilisée pour les contenus avant toute traduction.',
    ],
    'messages' => [
        'title' => 'Traduction des Messages',
		'description' => 'Mettre à jour Messages',
        'clear_cache_link' => 'Supprimer le cache',
        'clear_cache_loading' => 'Suppression du cache de l\'application...',
        'clear_cache_success' => 'Le cache de l\'application a été supprimé !',
        'clear_cache_hint' => 'Vous devez cliquer sur <strong>Supprimer le cache</strong> pour voir les modifications sur le site.',
        'scan_messages_link' => 'Rechercher des messages à traduire',
        'scan_messages_loading' => 'Recherche de nouveaux messages...',
        'scan_messages_success' => 'Recherche dans les fichiers du thème effectuée !',
        'scan_messages_hint' => 'Cliquez sur <strong>Rechercher des messages à traduire</strong> pour parcourir les fichiers du thème actif à la recherche de messages à traduire.',
        'hint_translate' => 'Vous pouvez traduire les messages affichés sur le site, les champs s\'enregistrent automatiquement.',
        'hide_translated' => 'Masquer les traductions',
    ],
];