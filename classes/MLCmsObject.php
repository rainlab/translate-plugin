<?php namespace RainLab\Translate\Classes;

use Cms\Classes\CmsCompoundObject;

/**
 * MLCmsObject represents a multi-lingual CMS compound object.
 *
 * @package rainlab\translate
 * @author Alexey Bobkov, Samuel Georges
 */
class MLCmsObject extends CmsCompoundObject
{
    /**
     * @var array The attributes that are mass assignable.
     */
    protected $fillable = [];

    /**
     * @var mixed locale
     */
    public static $locale;

    /**
     * @var mixed parent
     */
    public static $parent;

    /**
     * setContext
     */
    public static function setContext($locale, $parent)
    {
        static::$locale = $locale;
        static::$parent = $parent;
    }

    /**
     * forLocale
     */
    public static function forLocale($locale, $page)
    {
        static::setContext($locale, $page);

        return static::inTheme($page->theme);
    }

    /**
     * findLocale
     */
    public static function findLocale($locale, $page)
    {
        return static::forLocale($locale, $page)->find($page->fileName);
    }

    /**
     * getObjectTypeDirName returns the directory name corresponding to the object type.
     * For pages the directory name is "pages", for layouts - "layouts", etc.
     * @return string
     */
    public function getObjectTypeDirName()
    {
        $dirName = static::$parent->getObjectTypeDirName();

        if (strlen(static::$locale)) {
            $dirName .= '-' . static::$locale;
        }

        return $dirName;
    }
}
