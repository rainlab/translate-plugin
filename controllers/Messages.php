<?php namespace Rainlab\Translate\Controllers;

use BackendMenu;
use Backend\Classes\Controller;

/**
 * Messages Back-end Controller
 */
class Messages extends Controller
{
    public $implement = [
        'Backend.Behaviors.ListController'
    ];

    public $listConfig = 'config_list.yaml';

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Rainlab.Translate', 'translate', 'messages');
    }
}