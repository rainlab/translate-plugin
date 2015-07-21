<?php

namespace RainLab\Translate\Tests\Functional;

use RainLab\Translate\Models\Locale;
use RainLab\Translate\Models\Message;

class MessageTest extends \OctoberPluginTestCase
{
    protected $refreshPlugins = ['RainLab.Translate'];

    public function testImportMessages()
    {
        Message::importMessages(['Hello World!', 'Hello Piñata!']);
        $this->assertNotNull(Message::whereCode('hello.world')->first());
        $this->assertNotNull(Message::whereCode('hello.pinata')->first());
    }
}
