<?php

namespace Aerotur\Nemo\Updates;

use DB;
use Log;
use October\Rain\Database\Updates\Migration;
use Schema;

class UpdateAttributesCahngeIndexModel extends Migration
{
    public function up()
    {
        // Check if there are any non-numeric values
        $invalidRows = DB::table('rainlab_translate_attributes')
            ->whereNotNull('model_id')
            ->where('model_id', '!=', '')
            ->whereRaw("model_id REGEXP '[^0-9]'")
            ->count();

        if ($invalidRows > 0) {
            $message = "Found $invalidRows records with non-numeric values in column 'model_id'. Migration will not be applied.";
            Log::warning($message);
            return ;
        }
        Schema::table('rainlab_translate_attributes', function ($table) {
            $table->dropIndex(['model_type']);
            $table->dropIndex(['model_id']);
            $table->bigInteger('model_id')->nullable(false)->change();
            $table->index(['model_type', 'model_id']);
        });
    }

    public function down()
    {
        $modelIdType = Schema::getColumnType('rainlab_translate_attributes', 'model_id');
        if ($modelIdType === 'bigint') {
            Schema::table('rainlab_translate_attributes', function ($table) {
                $table->dropIndex(['model_type', 'model_id']);
                $table->string('model_id')->nullable()->change();
                $table->index(['model_id']);
                $table->index(['model_type']);
            });
        }
    }
}
