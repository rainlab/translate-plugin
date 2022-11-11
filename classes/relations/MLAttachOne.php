<?php namespace RainLab\Translate\Classes\Relations;

use RainLab\Translate\Classes\Translator;
use October\Rain\Database\Relations\AttachOne;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * MLAttachOne
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
class MLAttachOne extends AttachOne
{
    /**
     * __construct tweaks the morph class for translatable attachments
     */
    public function __construct(Builder $query, Model $parent, $type, $id, $isPublic, $localKey, $relationName = null)
    {
        parent::__construct($query, $parent, $type, $id, $isPublic, $localKey, $relationName);

        $this->morphClass .= ':' . Translator::instance()->getLocale();
    }
}
