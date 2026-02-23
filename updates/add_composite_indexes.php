<?php namespace RainLab\Translate\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class AddCompositeIndexes extends Migration
{
    public function up()
    {
        Schema::table('rainlab_translate_attributes', function($table) {
            $table->index(
                ['model_type', 'model_id', 'locale'],
                'translate_attrs_type_id_locale_index'
            );
        });

        Schema::table('rainlab_translate_indexes', function($table) {
            $table->index(
                ['model_type', 'model_id', 'locale', 'item'],
                'translate_idx_type_id_locale_item_index'
            );
        });
    }

    public function down()
    {
        Schema::table('rainlab_translate_attributes', function($table) {
            $table->dropIndex('translate_attrs_type_id_locale_index');
        });

        Schema::table('rainlab_translate_indexes', function($table) {
            $table->dropIndex('translate_idx_type_id_locale_item_index');
        });
    }
}
