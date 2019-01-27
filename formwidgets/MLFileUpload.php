<?php namespace RainLab\Translate\FormWidgets;

use Backend\FormWidgets\FileUpload;
use RainLab\Translate\Models\Locale as LocaleModel;
use RainLab\Translate\Classes\Translator;

/**
 * MLFileUpload Form Widget
 * Thanks to Adrien Girbone for this code
 * ref. https://github.com/Glitchbone/octobercms-filetranslate-plugin/tree/38823391d6ffd70c4678101fa134b033aa38ebac
 */
class MLFileUpload extends FileUpload
{

    protected $defaultAlias = 'mlfileupload';

    public function init()
    {
        parent::init();
        $this->addViewPath(array(
            $this->getViewPaths()[0],
            $this->guessViewPathFrom(get_parent_class($this), '/partials')
        ));
    }

    protected function loadAssets()
    {
        $this->assetPath = $this->guessViewPathFrom(get_parent_class($this), '/assets', true);
        parent::loadAssets();
    }

    /**
     * Loads the configuration form for an attachment, allowing title and description to be set.
     */
    public function onLoadAttachmentConfig()
    {
        $translator = Translator::instance();
        $this->vars['locales'] = LocaleModel::listEnabled();
        $this->vars['defaultLocale'] = $translator->getDefaultLocale();
        return parent::onLoadAttachmentConfig();
    }

    /**
     * Commit the changes of the attachment configuration form.
     */
    public function onSaveAttachmentConfig()
    {
        try {
            $fileModel = $this->getRelationModel();
            if (($fileId = post('file_id')) && ($file = $fileModel::find($fileId))) {

                foreach(post('MLFileTranslate') as $code => $attrs) {
                    foreach($attrs as $k => $v) {
                        $file->lang($code)->$k = $v;
                    }
                }

                $file->save();

                return ['displayName' => $file->title ?: $file->file_name];
            }

            throw new ApplicationException('Unable to find file, it may no longer exist');
        }
        catch (Exception $ex) {
            return json_encode(['error' => $ex->getMessage()]);
        }
    }

}
