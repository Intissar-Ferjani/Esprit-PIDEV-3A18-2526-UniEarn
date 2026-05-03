<?php

namespace App\Tests\Entity\messaging;

use App\Entity\messaging\Message;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
    private Message $message;

    protected function setUp(): void
    {
        $this->message = new Message();
    }

    public function testDefaultValues(): void
    {
        $this->assertSame('', $this->message->getContent());
        $this->assertFalse($this->message->isSeen());
        $this->assertNull($this->message->getIdMessage());
    }

    public function testSentDateIsSetOnConstruction(): void
    {
        $before = new \DateTime('-1 second');
        $after  = new \DateTime('+1 second');

        $this->assertGreaterThan($before, $this->message->getSentDate());
        $this->assertLessThan($after, $this->message->getSentDate());
    }

    public function testSetAndGetContent(): void
    {
        $result = $this->message->setContent('Hello UniEarn!');

        $this->assertSame('Hello UniEarn!', $this->message->getContent());
        $this->assertInstanceOf(Message::class, $result);
    }

    public function testSetContentReturnsFluentInterface(): void
    {
        $this->assertSame($this->message, $this->message->setContent('test'));
    }

    public function testSetAndGetSeen(): void
    {
        $this->message->setSeen(true);
        $this->assertTrue($this->message->isSeen());

        $this->message->setSeen(false);
        $this->assertFalse($this->message->isSeen());
    }

    public function testSetSeenReturnsFluentInterface(): void
    {
        $this->assertSame($this->message, $this->message->setSeen(true));
    }

    public function testSetAndGetChatId(): void
    {
        $this->message->setChatId(42);
        $this->assertSame(42, $this->message->getChatId());
    }

    public function testSetChatIdReturnsFluentInterface(): void
    {
        $this->assertSame($this->message, $this->message->setChatId(1));
    }

    public function testSetAndGetSenderId(): void
    {
        $this->message->setSenderId(7);
        $this->assertSame(7, $this->message->getSenderId());
    }

    public function testSetSenderIdReturnsFluentInterface(): void
    {
        $this->assertSame($this->message, $this->message->setSenderId(1));
    }

    public function testEmptyContentIsAllowed(): void
    {
        $this->message->setContent('');
        $this->assertSame('', $this->message->getContent());
    }

    public function testLongContentUpTo255Chars(): void
    {
        $long = str_repeat('a', 255);
        $this->message->setContent($long);
        $this->assertSame(255, strlen($this->message->getContent()));
    }

    public function testTwoMessagesHaveIndependentState(): void
    {
        $other = new Message();
        $this->message->setContent('First')->setSeen(true)->setChatId(1)->setSenderId(10);
        $other->setContent('Second')->setSeen(false)->setChatId(2)->setSenderId(20);

        $this->assertSame('First', $this->message->getContent());
        $this->assertSame('Second', $other->getContent());
        $this->assertNotSame($this->message->getChatId(), $other->getChatId());
    }
}
