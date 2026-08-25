<?php namespace RainLab\Translate\Tests\Unit\Console;

use PluginTestCase;
use System\Models\MailTemplate;
use RainLab\Translate\Console\ImportMailCommand;
use ReflectionMethod;

class ImportMailCommandTest extends PluginTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // Remove any test records left behind by an interrupted run
        MailTemplate::where('code', 'like', 'test.%')->delete();
    }

    public function testMakeSuffixTarget()
    {
        $locales = ['en', 'fr', 'fr-ca'];

        $this->assertEquals(['test.mail', 'fr'], $this->runMakeSuffixTarget('test.mail-fr', $locales));
        $this->assertEquals(['test.mail', 'fr-ca'], $this->runMakeSuffixTarget('test.mail-fr-ca', $locales));
        $this->assertNull($this->runMakeSuffixTarget('test.mail', $locales));
        $this->assertNull($this->runMakeSuffixTarget('test.mail-de', $locales));
        $this->assertNull($this->runMakeSuffixTarget('-fr', $locales));
    }

    public function testMigrateTemplateMovesTranslations()
    {
        $base = $this->makeTemplate('test.mail', 'Welcome', 'Hello');
        $suffix = $this->makeTemplate('test.mail-fr', 'Bienvenue', 'Bonjour');

        $this->assertTrue($this->runMigrateTemplate($suffix, 'test.mail', 'fr'));

        $base = MailTemplate::where('code', 'test.mail')->first();
        $this->assertEquals('Bienvenue', $base->getTranslation('subject', 'fr', false));
        $this->assertEquals('Bonjour', $base->getTranslation('content_html', 'fr', false));

        $this->assertNull(MailTemplate::where('code', 'test.mail-fr')->first());
    }

    public function testMigrateTemplateKeepsExistingTranslation()
    {
        $base = $this->makeTemplate('test.keep', 'Welcome', 'Hello');
        $base->setTranslation('subject', 'fr', 'Existant');
        $base->save();

        $suffix = $this->makeTemplate('test.keep-fr', 'Bienvenue', 'Bonjour');

        $this->assertTrue($this->runMigrateTemplate($suffix, 'test.keep', 'fr'));

        $base = MailTemplate::where('code', 'test.keep')->first();
        $this->assertEquals('Existant', $base->getTranslation('subject', 'fr', false));
        $this->assertEquals('Bonjour', $base->getTranslation('content_html', 'fr', false));
    }

    public function testMigrateTemplateWithoutBaseIsSkipped()
    {
        $suffix = $this->makeTemplate('test.orphan-fr', 'Bienvenue', 'Bonjour');

        $this->assertFalse($this->runMigrateTemplate($suffix, 'test.orphan', 'fr'));

        $this->assertNotNull(MailTemplate::where('code', 'test.orphan-fr')->first());
    }

    /**
     * makeTemplate creates a customized mail template record.
     */
    protected function makeTemplate(string $code, string $subject, string $content): MailTemplate
    {
        return MailTemplate::create([
            'code' => $code,
            'subject' => $subject,
            'description' => 'Test template',
            'content_html' => $content,
            'is_custom' => 1
        ]);
    }

    /**
     * runMakeSuffixTarget invokes the protected suffix parser on the command.
     */
    protected function runMakeSuffixTarget(string $code, array $locales): ?array
    {
        $command = new ImportMailCommand;
        $method = new ReflectionMethod($command, 'makeSuffixTarget');
        $method->setAccessible(true);

        return $method->invoke($command, $code, $locales);
    }

    /**
     * runMigrateTemplate invokes the protected migration on the command.
     */
    protected function runMigrateTemplate($record, string $baseCode, string $locale): bool
    {
        $command = new ImportMailCommand;
        $method = new ReflectionMethod($command, 'migrateTemplate');
        $method->setAccessible(true);

        return $method->invoke($command, $record, $baseCode, $locale);
    }
}
