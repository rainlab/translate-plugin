<?php namespace Rainlab\Translate\Controllers;

use Request;
use BackendMenu;
use Backend\Widgets\Grid;
use Backend\Classes\Controller;
use Rainlab\Translate\Models\Message;
use Rainlab\Translate\Models\Locale;

/**
 * Messages Back-end Controller
 */
class Messages extends Controller
{
    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('October.System', 'system', 'settings');

        $this->addJs('/plugins/rainlab/translate/assets/js/messages.js');
    }

    public function index()
    {
        $this->pageTitle = 'Translate Messages';
        $this->prepareGrid();
    }

    public function onRefresh()
    {
        $this->prepareGrid();
        return ['#messagesContainer' => $this->makePartial('messages')];
        return ['#gridHeaderContainer' => $this->makePartial('grid_header')];
    }

    public function prepareGrid()
    {
        $fromCode = post('locale_from', null);
        $toCode = post('locale_to', Locale::getDefault()->code);

        /*
         * Page vars
         */
        $this->vars['hideTranslated'] = post('hide_translated', false);
        $this->vars['defaultLocale'] = Locale::getDefault();
        $this->vars['locales'] = Locale::all();
        $this->vars['selectedFrom'] = $selectedFrom = Locale::findByCode($fromCode);
        $this->vars['selectedTo'] = $selectedTo = Locale::findByCode($toCode);

        /*
         * Make grid config, make default column read only
         */
        $config = $this->makeConfig('config_grid.yaml');
        if (!$selectedFrom) $config->columns['from']['readOnly'] = true;
        if (!$selectedTo) $config->columns['to']['readOnly'] = true;

        /*
         * Set up the default grid data, splice in selected language
         */
        $data = $this->getGridData();
        if ($selectedFrom) $data = $this->injectGridData($data, 'from', $selectedFrom->code);
        if ($selectedTo) $data = $this->injectGridData($data, 'to', $selectedTo->code);
        $config->data = array_values($data);

        /*
         * Make grid widget
         */
        $widget = new Grid($this, $config);
        $widget->bindToController();
        $this->vars['grid'] = $widget;
    }

    protected function getGridData()
    {
        $defaultCode = Locale::getDefault()->code;
        $messages = Message::whereLocale($defaultCode)->get();

        $data = [];
        foreach ($messages as $message) {
            $data[$message->msg_id] = ['from' => $message->msg_id, 'to' => $message->msg_id];
        }

        return $data;
    }

    protected function injectGridData($data, $type, $code)
    {
        $messages = Message::whereLocale($code)->get();

        foreach ($messages as $message) {
            if (!isset($data[$message->msg_id]))
                continue;

            $data[$message->msg_id][$type] = $message->msg_str ?: $message->msg_id;
        }

        return $data;
    }

}