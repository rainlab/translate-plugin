<?php

namespace RainLab\Translate\Tests\Unit;

use RainLab\Translate\Models\Message;

class MessageTest extends \OctoberPluginTestCase
{
    protected $requiresOctoberMigration = true;

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
        $this->assertEquals('foo.bar', Message::makeMessageCode('fòô bàř'));
        $this->assertEquals('test', Message::makeMessageCode(' ŤÉŚŢ '));
        $this->assertEquals('f.z.3', Message::makeMessageCode('φ = ź = 3'));
        $this->assertEquals('perevirka', Message::makeMessageCode('перевірка'));
        $this->assertEquals('lysaya.gora', Message::makeMessageCode('лысая гора'));
        $this->assertEquals('shchuka', Message::makeMessageCode('щука'));
        $this->assertEquals('foo', Message::makeMessageCode('foo 漢字')); // Chinese
        $this->assertEquals('xin.chao.the.gioi', Message::makeMessageCode('xin chào thế giới'));
        $this->assertEquals('xin.chao.the.gioi', Message::makeMessageCode('XIN CHÀO THẾ GIỚI'));
        $this->assertEquals('dam.phat.chet.luon', Message::makeMessageCode('đấm phát chết luôn'));
        $this->assertEquals('foo', Message::makeMessageCode('foo ')); // no-break space (U+00A0)
        $this->assertEquals('foo', Message::makeMessageCode('foo           ')); // spaces U+2000 to U+200A
        $this->assertEquals('foo', Message::makeMessageCode('foo ')); // narrow no-break space (U+202F)
        $this->assertEquals('foo', Message::makeMessageCode('foo ')); // medium mathematical space (U+205F)
        $this->assertEquals('foo', Message::makeMessageCode('foo　')); // ideographic space (U+3000)
    }

}
