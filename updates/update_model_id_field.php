<?php namespace RainLab\Translate\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;
use Illuminate\Support\Facades\DB;


class UpdateModelIdField extends Migration
{
    private $dbType;

    public function up()
    {
        $this->dbType = DB::connection()->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);

        switch ($this->dbType) {
            case 'pgsql':
                $this->updateFieldPostgreSqlWay();
                break;
            case 'mysql':
                $this->updateFieldGenericWay();
                break;
        }
    }

    public function down()
    {
        Schema::table('rainlab_translate_attributes', function ($table) {
            $table->string('model_id')->index()->nullable()->change();
        });

        Schema::table('rainlab_translate_indexes', function ($table) {
            $table->string('model_id')->index()->nullable()->change();
        });
    }


    private function updateFieldPostgreSqlWay(){
        $sql = 'ALTER TABLE rainlab_translate_attributes ALTER model_id TYPE INT USING model_id::integer';
        DB::statement($sql);

        $sql = 'ALTER TABLE rainlab_translate_indexes ALTER model_id TYPE INT USING model_id::integer';
        DB::statement($sql);
    }

    private function updateFieldGenericWay() {
        Schema::table('rainlab_translate_attributes', function ($table) {
            $table->unsignedInteger('model_id')->index()->nullable()->change();
        });

        Schema::table('rainlab_translate_indexes', function ($table) {
            $table->unsignedInteger('model_id')->index()->nullable()->change();
        });
    }

}
