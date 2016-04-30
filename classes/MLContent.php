<?php namespace RainLab\Translate\Classes;

/**
 * Represents a multi-lingual Static Content object.
 *
 * @package rainlab\translate
 * @author Alexey Bobkov, Samuel Georges
 */
class MLContent extends MLCmsObject
{
    public static function findLocale($locale, $page)
    {
        /*
         * Splice the active locale in to the filename
         * - content.htm -> content.en.htm
         */
        $fileName = substr_replace($page->fileName, '.'.$locale, strrpos($page->fileName, '.'), 0);

        return static::forLocale($locale, $page)->find($fileName);
    }

    /**
     * Returns the directory name corresponding to the object type.
     * Content does not use localized sub directories, but as file suffix instead.
     * @return string
     */
    public function getObjectTypeDirName()
    {
        return static::$parent->getObjectTypeDirName();
    }

    public function setMarkupHtmlAttribute($value)
    {
        return $this->markup = $value;
    }

    public function getMarkupHtmlAttribute()
    {
        return $this->markup;
    }
}
