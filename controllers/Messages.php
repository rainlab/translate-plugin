<?php namespace RainLab\Translate\Controllers;

use Lang;
use Flash;
use Request;
use BackendMenu;
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
        $this->bodyClass = 'slim-container breadcrumb-flush';
        $this->pageTitle = 'rainlab.translate::lang.messages.title';
        $this->prepareTable();
    }

    public function onRefresh()
    {
        $this->prepareTable();
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

    public function onChange()
    {
        // Assuming that the widget was initialized in the
        // controller constructor with the "table" alias.
        $dataSource = $this->widget->table->getDataSource();

        while ($records = $dataSource->readRecords(5)) {
            traceLog($records);
        }

        traceLog('hi');
        traceLog(post());
    }

    public function prepareTable()
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
         * Make table config, make default column read only
         */
        $config = $this->makeConfig('config_table.yaml');

        if (!$selectedFrom) {
            $config->columns['from']['readOnly'] = true;
        }
        if (!$selectedTo) {
            $config->columns['to']['readOnly'] = true;
        }

        /*
         * Make table widget
         */
        $widget = $this->makeWidget('Backend\Widgets\Table', $config);

        // $widget->bindEvent('table.dataChanged', function($action, $changes){
        //     if ($action == 'remove')
        //         $this->removeTableData($changes);
        //     else
        //         $this->updateTableData($changes);
        // });

        $widget->bindToController();

        /*
         * Populate data
         */
        $dataSource = $widget->getDataSource();

        $dataSource->bindEvent('data.getRecords', function($offset, $count) use ($selectedFrom, $selectedTo) {
            $messages = Message::limit($count)->offset($offset)->get();
            $result =  $this->processTableData($messages, $selectedFrom, $selectedTo);
            return $result;
        });

        $dataSource->bindEvent('data.getCount', function() {
            return Message::count();
        });

        $this->vars['table'] = $widget;
    }

    protected function processTableData($messages, $from, $to)
    {
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

    protected function removeTableData($changes)
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

    protected function updateTableData($changes)
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
