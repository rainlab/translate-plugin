<?php namespace RainLab\Translate\Tests\Unit\Models;

use RainLab\Translate\Models\Locale;
use RainLab\Translate\Models\Message;
use PluginTestCase;
use Model;

class MessageTest extends PluginTestCase
{
    public function testImportMessages()
    {
        Message::importMessages(['Hello World!', 'Hello Piñata!']);

        $this->assertNotNull(Message::whereCode(Message::makeMessageCode('Hello World!'))->first());
        $this->assertNotNull(Message::whereCode(Message::makeMessageCode('Hello Piñata!'))->first());

        Message::truncate();
    }

    public function testMakeMessageCode()
    {
        $baseMessageId = 'Hello World';
        $baseMessageCode = Message::makeMessageCode($baseMessageId);

        // casing should lead to the same code
        $this->assertEquals($baseMessageCode, Message::makeMessageCode(mb_strtolower($baseMessageId)));

        // heading/trailing spaces should be trimmed
        $this->assertEquals($baseMessageCode, Message::makeMessageCode(' ' . $baseMessageId));
        $this->assertEquals($baseMessageCode, Message::makeMessageCode($baseMessageId . ' '));
        $this->assertEquals($baseMessageCode, Message::makeMessageCode(' ' . $baseMessageId . ' '));

        // length of generated code should always be 32
        $veryLongString = str_repeat("10 charstr", 30);
        $this->assertTrue(strlen($veryLongString) > 250);
        $this->assertEquals(32, strlen(Message::makeMessageCode($veryLongString)));

        // unicode characters
        $this->assertEquals(Message::makeMessageCode('foo'), Message::makeMessageCode('foo　')); // ideographic space (U+3000)
        $this->assertNotEquals(Message::makeMessageCode('ete', Message::makeMessageCode('été'));
    }
}
