<?php namespace RainLab\Translate\Tests\Unit\Console;

use Db;
use Schema;
use Artisan;
use PluginTestCase;
use System\Models\File;
use October\Rain\Database\Model;

/**
 * ImportAttachmentsCommandTest covers converting translated attachments between the RainLab.Translate and core formats
 */
class ImportAttachmentsCommandTest extends PluginTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        if (!Schema::hasTable('translate_import_attachment_models')) {
            Schema::create('translate_import_attachment_models', function ($table) {
                $table->increments('id');
                $table->string('name')->nullable();
                $table->timestamps();
            });
        }

        ImportAttachmentModel::$activeLocale = 'en';
    }

    public function tearDown(): void
    {
        Schema::dropIfExists('translate_import_attachment_models');
        Db::table('system_files')->where('file_name', 'like', 'import-test-%')->delete();
        ImportAttachmentModel::$activeLocale = 'en';

        parent::tearDown();
    }

    /**
     * testImportMovesLocaleFromTypeToField confirms each locale lands on the field and the type loses its suffix
     */
    public function testImportMovesLocaleFromTypeToField()
    {
        $model = $this->makeModel();
        $type = ImportAttachmentModel::class;

        $this->insertFile('import-test-base.jpg', $type . ':en', $model->id, 'image');
        $this->insertFile('import-test-french.jpg', $type . ':fr', $model->id, 'image');
        $this->insertFile('import-test-cover.jpg', $type, $model->id, 'cover');

        $this->assertSame(0, Artisan::call('translate:import-attachments', ['--force' => true, '--default' => 'en']));

        $this->assertFileRow('import-test-base.jpg', $type, 'image');
        $this->assertFileRow('import-test-french.jpg', $type, 'image:fr');
        $this->assertFileRow('import-test-cover.jpg', $type, 'cover');

        ImportAttachmentModel::$activeLocale = 'fr';
        $this->assertSame('import-test-french.jpg', ImportAttachmentModel::find($model->id)->image->file_name);

        ImportAttachmentModel::$activeLocale = 'en';
        $this->assertSame('import-test-base.jpg', ImportAttachmentModel::find($model->id)->image->file_name);
    }

    /**
     * testImportDetachesFilesHiddenByRainLab confirms plain files on a translated relation stay hidden after import
     */
    public function testImportDetachesFilesHiddenByRainLab()
    {
        $model = $this->makeModel();
        $type = ImportAttachmentModel::class;

        $this->insertFile('import-test-stale.jpg', $type, $model->id, 'image');
        $this->insertFile('import-test-base.jpg', $type . ':en', $model->id, 'image');

        $this->assertSame(0, Artisan::call('translate:import-attachments', ['--force' => true, '--default' => 'en']));

        $this->assertFileRow('import-test-stale.jpg', null, null);
        $this->assertFileRow('import-test-base.jpg', $type, 'image');
        $this->assertSame('import-test-base.jpg', ImportAttachmentModel::find($model->id)->image->file_name);
    }

    /**
     * testImportHonorsModelOption confirms other model types are left alone
     */
    public function testImportHonorsModelOption()
    {
        $model = $this->makeModel();

        $this->insertFile('import-test-other.jpg', 'Acme\Other\Models\Post:fr', $model->id, 'image');
        $this->insertFile('import-test-french.jpg', ImportAttachmentModel::class . ':fr', $model->id, 'image');

        $this->assertSame(0, Artisan::call('translate:import-attachments', [
            '--force' => true,
            '--default' => 'en',
            '--model' => ImportAttachmentModel::class
        ]));

        $this->assertFileRow('import-test-other.jpg', 'Acme\Other\Models\Post:fr', 'image');
        $this->assertFileRow('import-test-french.jpg', ImportAttachmentModel::class, 'image:fr');
    }

    /**
     * testRollbackRestoresRainLabFormat confirms rollback suffixes the type for translatable relations only
     */
    public function testRollbackRestoresRainLabFormat()
    {
        $model = $this->makeModel();
        $type = ImportAttachmentModel::class;

        $this->insertFile('import-test-base.jpg', $type . ':en', $model->id, 'image');
        $this->insertFile('import-test-french.jpg', $type . ':fr', $model->id, 'image');
        $this->insertFile('import-test-cover.jpg', $type, $model->id, 'cover');

        $this->assertSame(0, Artisan::call('translate:import-attachments', ['--force' => true, '--default' => 'en']));
        $this->assertSame(0, Artisan::call('translate:import-attachments', ['--force' => true, '--default' => 'en', '--rollback' => true]));

        $this->assertFileRow('import-test-base.jpg', $type . ':en', 'image');
        $this->assertFileRow('import-test-french.jpg', $type . ':fr', 'image');
        $this->assertFileRow('import-test-cover.jpg', $type, 'cover');
    }

    /**
     * makeModel creates a saved model in the default locale
     */
    protected function makeModel(): ImportAttachmentModel
    {
        $model = new ImportAttachmentModel;
        $model->name = 'Product';
        $model->save();

        return $model;
    }

    /**
     * insertFile writes a file row directly, as RainLab.Translate stored it
     */
    protected function insertFile(string $name, string $type, $attachmentId, string $field)
    {
        Db::table('system_files')->insert([
            'disk_name' => uniqid() . '.jpg',
            'file_name' => $name,
            'file_size' => 0,
            'content_type' => 'image/jpeg',
            'attachment_type' => $type,
            'attachment_id' => $attachmentId,
            'field' => $field,
            'is_public' => true,
            'sort_order' => 1
        ]);
    }

    /**
     * assertFileRow checks the attachment type and field stored for a file
     */
    protected function assertFileRow(string $name, ?string $type, ?string $field)
    {
        $row = Db::table('system_files')->where('file_name', $name)->first();

        $this->assertSame($type, $row->attachment_type, $name);
        $this->assertSame($field, $row->field, $name);
    }
}

/**
 * ImportAttachmentModel is a translatable model with one translated and one shared attachment
 */
class ImportAttachmentModel extends Model
{
    use \October\Rain\Database\Traits\Translatable;
    use \October\Rain\Database\Traits\TranslatableAttachments;

    public static $activeLocale = 'en';

    public $table = 'translate_import_attachment_models';

    public $translatable = ['name', 'image'];

    public $attachOne = [
        'image' => File::class,
        'cover' => File::class
    ];

    protected function resolveTranslatableLocale()
    {
        return static::$activeLocale;
    }

    protected function resolveTranslatableDefaultLocale()
    {
        return 'en';
    }
}
