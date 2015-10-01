<?php

return [
    'plugin' => [
        'name' => 'Traduções',
        'description' => 'Permite sites com multi-idiomas.',
        'tab' => 'Tradução',
        'manage_locales' => 'Gerenciar locais',
        'manage_messages' => 'Gerenciar mensagens'
    ],
    'locale_picker' => [
        'component_name' => 'Seleção de idiomas',
        'component_description' => 'Exibe um campo de seleção de idiomas.',
    ],
    'locale' => [
        'title' => 'Gerenciar idiomas',
        'update_title' => 'Atualizar idioma',
        'create_title' => 'Criar idioma',
        'select_label' => 'Selecionar idioma',
        'default_suffix' => 'padrão',
        'unset_default' => '":locale" é o idioma padrão e não pode ser desativado.',
        'disabled_default' => '":locale" está desativado e não pode ser definido como padrão.',
        'name' => 'Nome',
        'code' => 'Código',
        'is_default' => 'Padrão',
        'is_default_help' => 'O idioma padrão apresenta o conteúdo antes das traduções.',
        'is_enabled' => 'Ativo',
        'is_enabled_help' => 'Idiomas desativados não estarão disponíveis na página.',
        'not_available_help' => 'Não há outros idiomas configurados.',
        'hint_locales' => 'Crie novos idiomas para traduzir o conteúdo da página. O idioma padrão apresenta o conteúdo antes das traduções.',
    ],
    'messages' => [
        'title' => 'Traduzir mensagens',
		'description' => 'Atualizar mensagens',
        'clear_cache_link' => 'Limpar cache',
        'clear_cache_loading' => 'Limpando o cache da aplicação...',
        'clear_cache_success' => 'Cache da aplicação limpo com sucesso!',
        'clear_cache_hint' => 'Talvez você terá que clicar em <strong>Limpar cache</strong> para visualizar as modificações na página.',
        'scan_messages_link' => 'Buscar por mensagens',
        'scan_messages_loading' => 'Buscando por novas mensagens...',
        'scan_messages_success' => 'Busca por novas mensagens nos arquivos concluída com sucesso!',
        'scan_messages_hint' => 'Clicando em <strong>Buscar por mensagens</strong> o sistema buscará por qualquer mensagem da aplicação que possa ser traduzida.',
        'hint_translate' => 'Aqui você pode raduzir as mensagens utilizadas na página, os campos são salvos automaticamente.',
        'hide_translated' => 'Ocultar traduzidas',
    ],
];