<?php namespace RainLab\Translate\Classes\Relations;

use October\Rain\Database\Relations\AttachMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * MLAttachMany
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
class MLAttachMany extends AttachMany
{
    /**
     * The original morph class without the translation context.
     * @var string
     */
    protected $originalMorphClass = '';

    /**
     * __construct tweaks the morph class for translatable attachments
     */
    public function __construct(Builder $query, Model $parent, $type, $id, $isPublic, $localKey, $relationName = null)
    {
        $previous = static::$constraints;
        static::$constraints = false;
        parent::__construct($query, $parent, $type, $id, $isPublic, $localKey, $relationName);
        static::$constraints = $previous;

        $this->originalMorphClass = $this->morphClass;
        $this->morphClass .= ':' . $parent->translateContext();

        $this->addConstraints();
    }

    /**
     * Ensure the correct morph class is used for the isModelRemovable check.
     */
    protected function isModelRemovable($model): bool
    {
        $previous = $this->morphClass;
        $this->morphClass = $this->originalMorphClass;

        $result = parent::isModelRemovable($model);

        $this->morphClass = $previous;

        return $result;
    }
}
