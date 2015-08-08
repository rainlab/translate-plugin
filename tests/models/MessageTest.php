<?php namespace RainLab\Translate\Tests\Models;

use RainLab\Translate\Models\Locale;
use RainLab\Translate\Models\Message;
use PluginTestCase;
use Model;

class MessageTest extends PluginTestCase
{
    public function seedDeprecatedData()
    {
        Model::unguard();

        Message::create([
            'code' => 'hello.pinata', // deprecated message code
            'message_data' => [
                'en-US' => 'Hello Piñata!',
                'zh-HK' => '皮納塔你好！'
            ]
        ]);

        Model::reguard();
    }

    public function testImportMessages()
    {
        Message::importMessages(['Hello World!', 'Hello Piñata!']);

        $this->assertNotNull(Message::whereCode('hello.world')->first());
        $this->assertNotNull(Message::whereCode('hello.piñata')->first());

        Message::truncate();
    }

    public function testGetCopiesFromDeprecated() {
        $this->seedDeprecatedData();

        Message::setContext('en-US');
        Message::get('Hello Piñata!');

        $newMessage = Message::whereCode('hello.piñata')->first();
        $deprecatedMessage = Message::whereCode('hello.pinata')->first();

        $this->assertNotNull($newMessage);
        $this->assertEquals($newMessage->messageData, $deprecatedMessage->messageData);

        Message::truncate();
    }

    public function testImportMessagesCopiesFromDeprecated() {
        $this->seedDeprecatedData();

        Message::importMessages(['Hello World!', 'Hello Piñata!']);

        $newMessage = Message::whereCode('hello.piñata')->first();
        $deprecatedMessage = Message::whereCode('hello.pinata')->first();

        $this->assertNotNull($newMessage);
        $this->assertEquals($newMessage->messageData, $deprecatedMessage->messageData);

        Message::truncate();
    }

    public function testMakeMessageCode()
    {
        $this->assertEquals('hello.world', Message::makeMessageCode('hello world'));
        $this->assertEquals('hello.world', Message::makeMessageCode(' hello world '));
        $this->assertEquals('hello.world', Message::makeMessageCode('hello-world'));
        $this->assertEquals('hello.world', Message::makeMessageCode('hello--world'));

        // casing
        $this->assertEquals('hello.world', Message::makeMessageCode('Hello World'));
        $this->assertEquals('hello.world', Message::makeMessageCode('Hello World!'));

        // underscores
        $this->assertEquals('helloworld', Message::makeMessageCode('hello_world'));
        $this->assertEquals('helloworld', Message::makeMessageCode('hello__world'));

        // length limit
        $veryLongString = str_repeat("10 charstr", 30);
        $this->assertTrue(strlen($veryLongString) > 250);
        $this->assertEquals(253, strlen(Message::makeMessageCode($veryLongString)));
        $this->assertStringEndsWith('...', Message::makeMessageCode($veryLongString));

        // unicode characters
        // brrowered some test cases from Stringy, the library Laravel's
        // `slug()` function depends on
        // https://github.com/danielstjules/Stringy/blob/master/tests/CommonTest.php
        $this->assertEquals('fòô.bàř', Message::makeMessageCode('fòô bàř'));
        $this->assertEquals('ťéśţ', Message::makeMessageCode(' ŤÉŚŢ '));
        $this->assertEquals('φ.ź.3', Message::makeMessageCode('φ = ź = 3'));
        $this->assertEquals('перевірка', Message::makeMessageCode('перевірка'));
        $this->assertEquals('лысая.гора', Message::makeMessageCode('лысая гора'));
        $this->assertEquals('щука', Message::makeMessageCode('щука'));
        $this->assertEquals('foo.漢字', Message::makeMessageCode('foo 漢字')); // Chinese
        $this->assertEquals('xin.chào.thế.giới', Message::makeMessageCode('xin chào thế giới'));
        $this->assertEquals('xin.chào.thế.giới', Message::makeMessageCode('XIN CHÀO THẾ GIỚI'));
        $this->assertEquals('đấm.phát.chết.luôn', Message::makeMessageCode('đấm phát chết luôn'));
        $this->assertEquals('foo', Message::makeMessageCode('foo ')); // no-break space (U+00A0)
        $this->assertEquals('foo', Message::makeMessageCode('foo           ')); // spaces U+2000 to U+200A
        $this->assertEquals('foo', Message::makeMessageCode('foo ')); // narrow no-break space (U+202F)
        $this->assertEquals('foo', Message::makeMessageCode('foo ')); // medium mathematical space (U+205F)
        $this->assertEquals('foo', Message::makeMessageCode('foo　')); // ideographic space (U+3000)
    }
}
