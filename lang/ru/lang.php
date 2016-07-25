<?php

return [
    'plugin' => [
        'name' => 'Translate',
        'description' => 'Настройки мультиязычности сайта.',
        'manage_locales' => 'Manage locales',
        'manage_messages' => 'Manage messages'
    ],
    'locale_picker' => [
        'component_name' => 'Locale Picker',
        'component_description' => 'Просмотр списка языков интерфейса.',
    ],
    'locale' => [
        'title' => 'Управление языками',
        'update_title' => 'Обновить язык',
        'create_title' => 'Создать язык',
        'select_label' => 'Выбрать язык',
        'default_suffix' => 'По умолчанию',
        'unset_default' => '":locale" уже установлен как язык по умолчанию.',
        'disabled_default' => '":locale" отключен и не может быть использован как язык по умолчанию.',
        'name' => 'Название',
        'code' => 'Код',
        'is_default' => 'По умолчанию',
        'is_default_help' => 'Использовать этот язык, как язык по умолчанию.',
        'is_enabled' => 'Включено',
        'is_enabled_help' => 'Сделать язык доступным в интерфейсе сайта.',
        'not_available_help' => 'Нет настроек других языков.',
        'hint_locales' => 'Создание новых переводов содержимого интерфейса сайта.',
    ],
    'messages' => [
        'title' => 'Перевод сообщений',
		'description' => 'Обновить сообщений',
        'clear_cache_link' => 'Очистить кэш',
        'clear_cache_loading' => 'Очистка кэша приложения...',
        'clear_cache_success' => 'Очистка кэша завершена успешно!',
        'clear_cache_hint' => 'Используйте кнопку <strong>Очистить кэш</strong>, чтобы увидеть изменения в интерфейсе сайта.',
        'scan_messages_link' => 'Сканирование сообщений',
        'scan_messages_loading' => 'Сканирование наличия новых сообщений...',
        'scan_messages_success' => 'Сканирование файлов шаблона темы успешно завершено!',
        'scan_messages_hint' => 'Используйте кнопку <strong>Сканирование сообщений</strong> для поиска новых ключей перевода активной темы интерфейса сайта.',
        'hint_translate' => 'Здесь вы можете переводить сообщения, которые используются в интерфейсе сайта.',
        'hide_translated' => 'Скрыть перевод',
    ],
];
