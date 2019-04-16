<?php namespace RainLab\Translate\FormWidgets;

use Backend\FormWidgets\FileUpload;

/**
 * ML FileUpload
 * Renders a multi-lingual form file uploader field.
 *
 * @package rainlab\translate
 * @author Adrien Girbone, Maria Vilaró
 */
class MLFileUpload extends FileUpload
{
    use \RainLab\Translate\Traits\MLControl;

    /**
     * {@inheritDoc}
     */
    protected $defaultAlias = 'mlfileupload';

    public function init()
    {
        parent::init();
        $this->initLocale();

        $viewPath = [];
        if ($this->isAvailable) {
            $viewPath[] = $this->getViewPaths()[0];
        }

        $viewPath[] = $this->guessViewPathFrom(get_parent_class($this), '/partials');

        $this->addViewPath($viewPath);
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
        $this->prepareLocaleVars();
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
                    $file->lang($code)->save();
                }
                $file->save();
                return ['displayName' => $file->lang($this->defaultLocale->code)->title ?: $file->lang($this->defaultLocale->code)->file_name];
            }
            throw new ApplicationException('Unable to find file, it may no longer exist');
        }
        catch (Exception $ex) {
            return json_encode(['error' => $ex->getMessage()]);
        }
    }
}
