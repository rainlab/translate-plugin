<?php namespace RainLab\Translate\Models;

use System\Models\File as FileBase;

/**
 * MLFile makes file attachments translatable
 *
 * @package rainlab\translate
 * @author Alexey Bobkov, Samuel Georges
 */
class MLFile extends FileBase
{
    /**
     * @var array implement behaviors
     */
    public $implement = [
        \RainLab\Translate\Behaviors\TranslatableModel::class
    ];

    /**
     * @var array translatable attributes
     */
    public $translatable = ['title', 'description'];
}
