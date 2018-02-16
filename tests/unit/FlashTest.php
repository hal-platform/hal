<?php

namespace Hal\UI;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class FlashTest extends TestCase
{
    public function testAddingMessages()
    {
        $flash = new Flash;

        $this->assertSame(false, $flash->hasMessages());
        $this->assertCount(0, $flash->getMessages());

        $actual = $flash
            ->withMessage('error', 'msg 1')
            ->withMessage('success', 'msg 2');

        $this->assertInstanceOf(Flash::class, $actual);

        $this->assertSame(true, $flash->hasMessages());
        $this->assertCount(2, $flash->getMessages());
    }

    public function testRemovingMessages()
    {
        $flash = new Flash([
            'derp',
            [
                'message' => 'derp'
            ],
            [
                'type' => 'derp'
            ],

            [
                'type' => 'error',
                'message' => 'msg 1'
            ],
            [
                'type' => 'success',
                'message' => 'msg 2'
            ]
        ]);

        $this->assertCount(2, $flash->getMessages());

        $flash->removeMessages();

        $this->assertCount(0, $flash->getMessages());
    }

    public function testFlushingMessages()
    {
        $flash = new Flash([
            [
                'type' => 'error',
                'message' => 'msg 1'
            ],
            [
                'type' => 'success',
                'message' => 'msg 2'
            ]
        ]);

        $this->assertSame(false, $flash->hasChanged());

        $messages = $flash->flush();
        $this->assertSame(true, $flash->hasChanged());


        $this->assertSame('msg 1', $messages[0]['message']);
        $this->assertSame('msg 2', $messages[1]['message']);
    }

    public function testInvalidMessageTypeThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid flash type "derp" specified.');

        $flash = new Flash;

        $flash->withMessage('derp', 'derp');
    }

    public function testSerializeToCookie()
    {
        $flash = new Flash([
            [
                'type' => 'error',
                'message' => 'msg 1'
            ],
            [
                'type' => 'success',
                'message' => 'msg 2'
            ]
        ]);

        $expected = '[{"type":"error","message":"msg 1","details":""},{"type":"success","message":"msg 2","details":""}]';
        $actual = Flash::toCookie($flash);

        $this->assertSame($expected, $actual);
    }

    public function testDeserializeFromCookie()
    {
        $data = '[{"type":"error","message":"msg 1","details":""},{"type":"success","message":"msg 2","details":""}]';
        $actual = Flash::fromCookie($data);

        $this->assertCount(2, $actual->getMessages());

        $this->assertSame('msg 1', $actual->getMessages()[0]['message']);
        $this->assertSame('msg 2', $actual->getMessages()[1]['message']);
    }

    public function testBadDeserializeFromCookie()
    {
        $actual = Flash::fromCookie('');
        $this->assertSame(null, $actual);

        $actual = Flash::fromCookie('"derp"');
        $this->assertSame(null, $actual);
    }
}
