<?php namespace RainLab\Translate\Classes\EventRegistry;

/**
 * FileHandler for file model translation events
 *
 * @package rainlab\translate
 * @author Alexey Bobkov, Samuel Georges
 */
class FileHandler
{
    /**
     * register events
     */
    public function register()
    {
        $this->extendSystemFileModel();
    }

    /**
     * boot events
     */
    public function boot()
    {
    }

    /**
     * extendSystemFileModel extends the File model to support resolving translated file attachments.
     */
    protected function extendSystemFileModel()
    {
        \System\Models\File::extend(function($model) {
            $model->bindEvent('model.beforeGetAttribute', function ($key) use (&$model) {
                // Return the attachment_type without the added :locale suffix.
                if ($key === 'attachment_type') {
                    $value = $model->attributes[$key] ?? '';
                    if (!str_contains($value, ':')) {
                        return $value;
                    }

                    $parts = array_slice(explode(':', $value), 0, -1);

                    return implode(':', $parts);
                }
            });
        });
    }
}
