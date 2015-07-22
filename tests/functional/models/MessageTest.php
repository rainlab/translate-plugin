<?php

namespace RainLab\Translate\Tests\Functional;

use \Model;
use RainLab\Translate\Models\Locale;
use RainLab\Translate\Models\Message;

class MessageTest extends \OctoberPluginTestCase
{
    protected $refreshPlugins = ['RainLab.Translate'];

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
}
