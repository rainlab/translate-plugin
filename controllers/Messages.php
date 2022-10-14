<?php namespace RainLab\Translate\Controllers;

use Flash;
use BackendMenu;
use Backend\Classes\Controller;
use RainLab\Translate\Models\Message;
use RainLab\Translate\Classes\Locale;
use RainLab\Translate\Classes\ThemeScanner;
use System\Helpers\Cache as CacheHelper;
use System\Classes\SettingsManager;

/**
 * Messages Backend Controller
 */
class Messages extends Controller
{
    /**
     * @var array implement behaviors
     */
    public $implement = [
        \Backend\Behaviors\ImportExportController::class,
    ];

    /**
     * @var mixed importExportConfig
     */
    public $importExportConfig = 'config_import_export.yaml';

    /**
     * @var mixed requiredPermissions
     */
    public $requiredPermissions = ['rainlab.translate.manage_messages'];

    /**
     * @var mixed hideTranslated
     */
    protected $hideTranslated = false;

    /**
     * @var mixed pruneMessages
     */
    protected $pruneMessages = false;

    /**
     * __construct
     */
    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('October.System', 'system', 'settings');
        SettingsManager::setContext('RainLab.Translate', 'messages');

        $this->addJs('/plugins/rainlab/translate/assets/js/messages.js');
        $this->addCss('/plugins/rainlab/translate/assets/css/messages.css');
    }

    /**
     * index
     */
    public function index()
    {
        $this->pageTitle = 'Translate Messages';
        $this->prepareTable();
    }

    /**
     * importExportGetFileName
     */
    public function importExportGetFileName()
    {
        $locale = post('ExportOptions[locale]', 'xx');
        return 'messages-'.$locale;
    }

    /**
     * onRefresh
     */
    public function onRefresh()
    {
        $this->prepareTable();
        return ['#messagesContainer' => $this->makePartial('messages')];
    }

    /**
     * onClearCache
     */
    public function onClearCache()
    {
        CacheHelper::clear();

        Flash::success(__("Cleared the application cache successfully!"));
    }

    /**
     * onLoadScanMessagesForm
     */
    public function onLoadScanMessagesForm()
    {
        return $this->makePartial('scan_messages_form');
    }

    /**
     * onScanMessages
     */
    public function onScanMessages()
    {
        if (post('purge_messages', false)) {
            Message::truncate();
        }

        if (post('purge_deleted_messages', false)) {
            $currentMessages = Message::getMessages();
        }

        $scanner = ThemeScanner::scan();

        if (post('purge_deleted_messages', false)) {
            $scanner->purgeMissingMessages($currentMessages);
        }

        Flash::success(__("Scanned theme template files successfully!"));

        return $this->onRefresh();
    }

    /**
     * getActiveLocale
     */
    public function getActiveLocale()
    {
        return post('locale_to', Locale::getSiteLocaleFromContext());
    }

    /**
     * prepareTable
     */
    public function prepareTable()
    {
        $toCode = $this->getActiveLocale();
        $this->hideTranslated = post('hide_translated', false);
        $this->pruneMessages = post('prune_messages', false);

        // Page vars
        $this->vars['hideTranslated'] = $this->hideTranslated;
        $this->vars['pruneMessages'] = $this->pruneMessages;
        $this->vars['defaultLocale'] = Locale::getDefault();
        $this->vars['locales'] = Locale::all();
        $this->vars['selectedTo'] = Locale::findByCode($toCode);

        // Make table config, make default column read only
        $config = $this->makeConfig('config_table.yaml');

        // Make table widget
        $widget = $this->makeWidget(\Backend\Widgets\Table::class, $config);
        $widget->bindToController();

        // Populate data
        $dataSource = $widget->getDataSource();

        $dataSource->bindEvent('data.getRecords', function($offset, $count) use ($toCode) {
            $messages = $this->listMessagesForDatasource($toCode, [
                'withUsage' => $this->pruneMessages,
                'offset' => $offset,
                'count' => $count
            ]);

            return $this->processTableData($messages);
        });

        $dataSource->bindEvent('data.searchRecords', function($search, $offset, $count) use ($toCode) {
            $messages = $this->listMessagesForDatasource($toCode, [
                'withUsage' => $this->pruneMessages,
                'search' => $search,
                'offset' => $offset,
                'count' => $count
            ]);

            return $this->processTableData($messages);
        });

        $dataSource->bindEvent('data.getCount', function() {
            return Message::getLastCount();
        });

        $dataSource->bindEvent('data.updateRecord', function($key, $data) {
            (new Message)->updateMessage($this->getActiveLocale(), $key, $data['to']);
        });

        $dataSource->bindEvent('data.deleteRecord', function($key) {
            (new Message)->deleteMessage($key);
        });

        $this->vars['table'] = $widget;
    }

    /**
     * isHideTranslated
     */
    protected function isHideTranslated()
    {
        return post('hide_translated', false);
    }

    /**
     * listMessagesForDatasource
     */
    protected function listMessagesForDatasource($locale, $options = [])
    {
        return Message::getMessages($locale, $options);
    }

    /**
     * processTableData
     */
    protected function processTableData($messages)
    {
        $data = [];
        foreach ($messages as $key => $message) {
            if ($this->hideTranslated && $message !== null) {
                continue;
            }

            $data[] = [
                'id' => $key,
                'from' => $key,
                'to' => $message
            ];
        }

        return $data;
    }
}
