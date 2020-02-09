<?php namespace RainLab\Translate\Updates;

use October\Rain\Database\Updates\Seeder;
use LocaleModel as Locale;

class SeedAllTables extends Seeder
{

    public function run()
    {
        if(Locale::count() === 0) {
            Locale::create([
                'code' => 'en',
                'name' => 'English',
                'is_default' => true,
                'is_enabled' => true
            ]);
        }
    }

}
