<?php namespace RainLab\Translate\Classes;

use RainLab\Translate\Classes\EventRegistry\CmsHandler;
use RainLab\Translate\Classes\EventRegistry\FormFieldHandler;
use RainLab\Translate\Classes\EventRegistry\MailHandler;
use RainLab\Translate\Classes\EventRegistry\FileHandler;
use RainLab\Translate\Classes\EventRegistry\StaticPageHandler;
use RainLab\Translate\Classes\EventRegistry\TemplateListHandler;

/**
 * EventRegistry for bootstrapping events
 *
 * @package rainlab\translate
 * @author Alexey Bobkov, Samuel Georges
 */
class EventRegistry
{
    use \October\Rain\Support\Traits\Singleton;

    /**
     * @var CmsHandler
     */
    protected $cmsHandler;

    /**
     * registerEvents
     */
    public function registerEvents()
    {
        $this->cmsHandler = new CmsHandler;
        $this->cmsHandler->register();

        (new FormFieldHandler)->register();
        (new MailHandler)->register();
        (new FileHandler)->register();
        (new StaticPageHandler)->register();
    }

    /**
     * bootEvents
     */
    public function bootEvents()
    {
        $this->cmsHandler->boot();

        (new StaticPageHandler)->boot();
        (new TemplateListHandler)->boot();
    }

    /**
     * importMessagesFromTheme
     */
    public function importMessagesFromTheme($themeCode)
    {
        $this->cmsHandler->importMessagesFromTheme($themeCode);
    }

    /**
     * setMessageContext for translation caching.
     */
    public function setMessageContext($page)
    {
        $this->cmsHandler->setMessageContext($page);
    }
}
