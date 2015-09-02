<?php namespace RainLab\Translate\Controllers;

use Lang;
use Flash;
use Request;
use BackendMenu;
use Backend\Widgets\Grid;
use Backend\Classes\Controller;
use RainLab\Translate\Models\Message;
use RainLab\Translate\Models\Locale;
use RainLab\Translate\Classes\ThemeScanner;
use System\Helpers\Cache as CacheHelper;
use System\Classes\SettingsManager;

/**
 * Messages Back-end Controller
 */
class Messages extends Controller
{
    public $requiredPermissions = ['rainlab.translate.manage_messages'];

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('October.System', 'system', 'settings');
        SettingsManager::setContext('RainLab.Translate', 'messages');

        $this->addJs('/plugins/rainlab/translate/assets/js/messages.js');
        $this->addCss('/plugins/rainlab/translate/assets/css/messages.css');
    }

    public function index()
    {
        $this->bodyClass = 'slim-container';
        $this->pageTitle = 'rainlab.translate::lang.messages.title';
        $this->prepareGrid();
    }

    public function onRefresh()
    {
        $this->prepareGrid();
        return ['#messagesContainer' => $this->makePartial('messages')];
    }

    public function onClearCache()
    {
        CacheHelper::clear();
        Flash::success(Lang::get('rainlab.translate::lang.messages.clear_cache_success'));
    }

    public function onScanMessages()
    {
        ThemeScanner::scan();
        Flash::success(Lang::get('rainlab.translate::lang.messages.scan_messages_success'));

        return $this->onRefresh();
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
        $config->data = $this->getGridData($selectedFrom, $selectedTo);

        if (!$selectedFrom) {
            $config->columns['from']['readOnly'] = true;
        }
        if (!$selectedTo) {
            $config->columns['to']['readOnly'] = true;
        }

        /*
         * Make grid widget
         */
        $widget = new Grid($this, $config);
        $widget->bindEvent('grid.dataChanged', function($action, $changes) {
            if ($action == 'remove') {
                $this->removeGridData($changes);
            }
            else {
                $this->updateGridData($changes);
            }
        });

        $widget->bindToController();
        $this->vars['grid'] = $widget;
    }

    protected function getGridData($from, $to)
    {
        $messages = Message::all();

        $fromCode = $from ? $from->code : null;
        $toCode = $to ? $to->code : null;

        $data = [];
        foreach ($messages as $message) {
            $data[] = [
                'code' => $message->code,
                'from' => $message->forLocale($fromCode),
                'to' => $message->forLocale($toCode)
            ];
        }

        return $data;
    }

    protected function removeGridData($changes)
    {
        if (!is_array($changes)) {
            return;
        }

        foreach ($changes as $change) {
            if (!$code = array_get($change, 'rowData.code')) {
                continue;
            }

            if (!$item = Message::whereCode($code)->first()) {
                continue;
            }

            $item->delete();
        }
    }

    protected function updateGridData($changes)
    {
        if (!is_array($changes)) {
            return;
        }

        foreach ($changes as $change) {
            if (!$code = array_get($change, 'rowData.code')) {
                continue;
            }

            if (!$columnType = array_get($change, 'keyName')) {
                continue;
            }

            if ($columnType != 'to' && $columnType != 'from') {
                continue;
            }

            if (!$locale = post('locale_'.$columnType)) {
                continue;
            }

            if (!$item = Message::whereCode($code)->first()) {
                continue;
            }

            $newValue = array_get($change, 'newValue');
            $item->toLocale($locale, $newValue);
        }
    }
}
