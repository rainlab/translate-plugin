<?php namespace RainLab\Translate\FormWidgets;

use RainLab\Blog\Models\Post;

/**
 * ML Blog Markdown
 * Renders a multi-lingual text field.
 *
 * @property  originalViewPath
 * @package rainlab\translate
 * @author Rafał Soboń
 */
class MLBlogMarkdown extends MLMarkdownEditor
{

    public function init() {
        $this->actAsParent();
        parent::init();
    }

    public function getSaveValue($value)
    {
        $localeData = $this->getLocaleSaveData();

        /*
         * Set the translated values to the model
         */
        if ($this->model->methodExists('setTranslateAttribute')) {
            foreach ($localeData as $locale => $value) {
                $this->model->setTranslateAttribute($this->columnName, $value, $locale);
                $this->model->setTranslateAttribute('content_html', Post::formatHtml($value), $locale);

            }
        }

        return array_get($localeData, $this->defaultLocale->code, $value);
    }

    private function actAsParent($switch = true)
    {
        $this->assetPath = "/plugins/rainlab/translate/formwidgets/mlmarkdowneditor/assets";
        $this->viewPath = base_path()."/plugins/rainlab/translate/formwidgets/mlmarkdowneditor/partials";
    }

}
