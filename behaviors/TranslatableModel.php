<?php namespace RainLab\Translate\Behaviors;

use Db;
use DbDongle;
use RainLab\Translate\Classes\TranslatableBehavior;

/**
 * Translatable model extension
 *
 * Usage:
 *
 * In the model class definition:
 *
 *   public $implement = ['@RainLab.Translate.Behaviors.TranslatableModel'];
 *
 *   public $translatable = ['name', 'content'];
 *
 */
class TranslatableModel extends TranslatableBehavior
{
    /**
     * __construct
     */
    public function __construct($model)
    {
        parent::__construct($model);

        $model->morphMany['translations'] = [
            \RainLab\Translate\Models\Attribute::class,
            'name' => 'model'
        ];

        // Replace attachments with translatable ones
        $this->extendFileModels('attachOne');
        $this->extendFileModels('attachMany');

        // Clean up indexes when this model is deleted
        $model->bindEvent('model.afterDelete', function() use ($model) {
            Db::table('rainlab_translate_attributes')
                ->where('model_id', $model->getKey())
                ->where('model_type', get_class($model))
                ->delete();

            Db::table('rainlab_translate_indexes')
                ->where('model_id', $model->getKey())
                ->where('model_type', get_class($model))
                ->delete();
        });
    }

    /**
     * extendFileModels will swap the standard File model with MLFile instead
     */
    protected function extendFileModels(string $relationGroup)
    {
        foreach ($this->model->$relationGroup as $relationName => $relationObj) {
            $relationClass = is_array($relationObj) ? $relationObj[0] : $relationObj;

            // Custom implementation
            if ($relationClass !== \System\Models\File::class) {
                continue;
            }

            // Normalize definition
            if (!is_array($relationObj)) {
                $relationObj = (array) $relationObj;
            }

            // Translatable individual file models
            if (in_array($relationName, $this->getTranslatableAttributes())) {
                $relationObj['relationClass'] = $relationGroup === 'attachOne'
                    ? \RainLab\Translate\Classes\Relations\MLAttachOne::class
                    : \RainLab\Translate\Classes\Relations\MLAttachMany::class;
            }
            // Translate file models attributes only
            else {
                $relationObj[0] = \RainLab\Translate\Models\MLFile::class;
            }

            $this->model->$relationGroup[$relationName] = $relationObj;
        }
    }

    /**
     * scopeTransWhere applies a translatable index to a basic query. This scope will join the
     * index table and can be executed neither more than once, nor with scopeTransOrder.
     * @param  Builder $query
     * @param  string $index
     * @param  string $value
     * @param  string $locale
     * @return Builder
     */
    public function scopeTransWhere($query, $index, $value, $locale = null, $operator = '=')
    {
        return $this->transWhereInternal($query, $index, $value, [
            'locale' => $locale,
            'operator' => $operator
        ]);
    }

    /**
     * scopeTransWhereNoFallback is identical to scopeTransWhere except it will not
     * use a fallback query when there are no indexes found.
     * @see scopeTransWhere
     */
    public function scopeTransWhereNoFallback($query, $index, $value, $locale = null, $operator = '=')
    {
        // Ignore translatable indexes in default locale context
        if (($locale ?: $this->translatableContext) === $this->translatableDefault) {
            return $query->where($index, $operator, $value);
        }

        return $this->transWhereInternal($query, $index, $value, [
            'locale' => $locale,
            'operator' => $operator,
            'noFallback' => true
        ]);
    }

    /**
     * transWhereInternal
     * @link https://github.com/rainlab/translate-plugin/pull/623
     */
    protected function transWhereInternal($query, $index, $value, $options = [])
    {
        extract(array_merge([
            'locale' => null,
            'operator' => '=',
            'noFallback' => false
        ], $options));

        if (!$locale) {
            $locale = $this->translatableContext;
        }

        // Separate query into two separate queries for improved performance
        $translateIndexes = Db::table('rainlab_translate_indexes')
            ->where('rainlab_translate_indexes.model_type', '=', $this->getClass())
            ->where('rainlab_translate_indexes.locale', '=', $locale)
            ->where('rainlab_translate_indexes.item', $index)
            ->where('rainlab_translate_indexes.value', $operator, $value)
            ->pluck('model_id')
        ;

        if ($translateIndexes->count() || $noFallback) {
            $query->whereIn($this->model->getQualifiedKeyName(), $translateIndexes);
        }
        else {
            $query->where($index, $operator, $value);
        }

        return $query;
    }

    /**
     * Applies a sort operation with a translatable index to a basic query. This scope will join the index table.
     * @param  Builder $query
     * @param  string $index
     * @param  string $direction
     * @param  string $locale
     * @return Builder
     */
    public function scopeTransOrderBy($query, $index, $direction = 'asc', $locale = null)
    {
        if (!$locale) {
            $locale = $this->translatableContext;
        }
        $indexTableAlias = 'rainlab_translate_indexes_' . $index . '_' . $locale;

        $query->select(
            $this->model->getTable().'.*',
            Db::raw('COALESCE(' . $indexTableAlias . '.value, '. $this->model->getTable() .'.'.$index.') AS translate_sorting_key')
        );

        $query->orderBy('translate_sorting_key', $direction);

        $this->joinTranslateIndexesTable($query, $locale, $index, $indexTableAlias);

        return $query;
    }

    /**
     * Joins the translatable indexes table to a query.
     * @param  Builder $query
     * @param  string $locale
     * @param  string $indexTableAlias
     * @return Builder
     */
    protected function joinTranslateIndexesTable($query, $locale, $index, $indexTableAlias)
    {
        $joinTableWithAlias = 'rainlab_translate_indexes as ' . $indexTableAlias;
        // check if table with same name and alias is already joined
        if (collect($query->getQuery()->joins)->contains('table', $joinTableWithAlias)) {
            return $query;
        }

        $query->leftJoin($joinTableWithAlias, function($join) use ($locale, $index, $indexTableAlias) {
            $join
                ->on(Db::raw(DbDongle::cast($this->model->getQualifiedKeyName(), 'TEXT')), '=', $indexTableAlias . '.model_id')
                ->where($indexTableAlias . '.model_type', '=', $this->getClass())
                ->where($indexTableAlias . '.item', '=', $index)
                ->where($indexTableAlias . '.locale', '=', $locale);
        });

        return $query;
    }

    /**
     * Saves the translation data in the join table.
     * @param  string $locale
     * @return void
     */
    protected function storeTranslatableData($locale = null)
    {
        if (!$locale) {
            $locale = $this->translatableContext;
        }

        /*
         * Model doesn't exist yet, defer this logic in memory
         */
        if (!$this->model->exists) {
            $this->model->bindEventOnce('model.afterCreate', function() use ($locale) {
                $this->storeTranslatableData($locale);
            });

            return;
        }

        /**
         * @event model.translate.resolveComputedFields
         * Resolve computed fields before saving
         *
         * Example usage:
         *
         * Override Model's __construct method
         *
         * public function __construct(array $attributes = [])
         * {
         *     parent::__construct($attributes);
         *
         *     $this->bindEvent('model.translate.resolveComputedFields', function ($locale) {
         *         return [
         *             'content_html' =>
         *                 self::formatHtml($this->asExtension('TranslatableModel')
         *                     ->getAttributeTranslated('content', $locale))
         *         ];
         *     });
         * }
         *
         */
        $computedFields = $this->model->fireEvent('model.translate.resolveComputedFields', [$locale], true);
        if (is_array($computedFields)) {
            $this->translatableAttributes[$locale] = array_merge($this->translatableAttributes[$locale], $computedFields);
        }

        $this->storeTranslatableBasicData($locale);
        $this->storeTranslatableIndexData($locale);

        // Trigger event workflow
        if ($this->model->usesTimestamps()) {
            $this->model->updateTimestamps();
        }
    }

    /**
     * Saves the basic translation data in the join table.
     * @param  string $locale
     * @return void
     */
    protected function storeTranslatableBasicData($locale = null)
    {
        $data = json_encode($this->translatableAttributes[$locale], JSON_UNESCAPED_UNICODE);

        $obj = Db::table('rainlab_translate_attributes')
            ->where('locale', $locale)
            ->where('model_id', $this->model->getKey())
            ->where('model_type', $this->getClass());

        if ($obj->count() > 0) {
            $obj->update(['attribute_data' => $data]);
        }
        else {
            Db::table('rainlab_translate_attributes')->insert([
                'locale' => $locale,
                'model_id' => $this->model->getKey(),
                'model_type' => $this->getClass(),
                'attribute_data' => $data
            ]);
        }
    }

    /**
     * Saves the indexed translation data in the join table.
     * @param  string $locale
     * @return void
     */
    protected function storeTranslatableIndexData($locale = null)
    {
        $optionedAttributes = $this->getTranslatableAttributesWithOptions();
        if (!count($optionedAttributes)) {
            return;
        }

        $data = $this->translatableAttributes[$locale];

        foreach ($optionedAttributes as $attribute => $options) {
            if (!array_get($options, 'index', false)) {
                continue;
            }

            $value = array_get($data, $attribute);

            $obj = Db::table('rainlab_translate_indexes')
                ->where('locale', $locale)
                ->where('model_id', $this->model->getKey())
                ->where('model_type', $this->getClass())
                ->where('item', $attribute);

            $recordExists = $obj->count() > 0;

            if (!strlen($value)) {
                if ($recordExists) {
                    $obj->delete();
                }
                continue;
            }

            if ($recordExists) {
                $obj->update(['value' => $value]);
            }
            else {
                Db::table('rainlab_translate_indexes')->insert([
                    'locale' => $locale,
                    'model_id' => $this->model->getKey(),
                    'model_type' => $this->getClass(),
                    'item' => $attribute,
                    'value' => $value
                ]);
            }
        }
    }

    /**
     * Loads the translation data from the join table.
     * @param  string $locale
     * @return array
     */
    protected function loadTranslatableData($locale = null)
    {
        if (!$locale) {
            $locale = $this->translatableContext;
        }

        if (!$this->model->exists) {
            return $this->translatableAttributes[$locale] = [];
        }

        $obj = $this->model->translations->first(function ($value, $key) use ($locale) {
            return $value->attributes['locale'] === $locale;
        });

        $result = $obj ? json_decode($obj->attribute_data, true) : [];

        return $this->translatableOriginals[$locale] = $this->translatableAttributes[$locale] = $result;
    }

    /**
     * Returns the class name of the model. Takes any
     * custom morphMap aliases into account.
     *
     * @return string
     */
    protected function getClass()
    {
        return $this->model->getMorphClass();
    }
}
