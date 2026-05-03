<?php

namespace App\Tests\Entity\notification;

use App\Entity\notification\Notification;
use PHPUnit\Framework\TestCase;

class NotificationTest extends TestCase
{
    private Notification $notification;

    protected function setUp(): void
    {
        $this->notification = new Notification();
    }

    public function testDefaultValues(): void
    {
        $this->assertNull($this->notification->getId());
        $this->assertNull($this->notification->getLink());
        $this->assertFalse($this->notification->isRead());
    }

    public function testCreatedAtIsSetOnConstruction(): void
    {
        $before = new \DateTime('-1 second');
        $after  = new \DateTime('+1 second');

        $this->assertGreaterThan($before, $this->notification->getCreatedAt());
        $this->assertLessThan($after, $this->notification->getCreatedAt());
    }

    public function testSetAndGetUserId(): void
    {
        $result = $this->notification->setUserId(12);

        $this->assertSame(12, $this->notification->getUserId());
        $this->assertInstanceOf(Notification::class, $result);
    }

    public function testSetUserIdReturnsFluentInterface(): void
    {
        $this->assertSame($this->notification, $this->notification->setUserId(1));
    }

    public function testSetAndGetType(): void
    {
        foreach (['LIKE', 'DISLIKE', 'COMMENT', 'MESSAGE'] as $type) {
            $this->notification->setType($type);
            $this->assertSame($type, $this->notification->getType());
        }
    }

    public function testSetTypeReturnsFluentInterface(): void
    {
        $this->assertSame($this->notification, $this->notification->setType('LIKE'));
    }

    public function testSetAndGetContent(): void
    {
        $this->notification->setContent('Someone liked your post.');
        $this->assertSame('Someone liked your post.', $this->notification->getContent());
    }

    public function testSetContentReturnsFluentInterface(): void
    {
        $this->assertSame($this->notification, $this->notification->setContent('test'));
    }

    public function testSetAndGetLink(): void
    {
        $this->notification->setLink('/freelancer/forum#post-5');
        $this->assertSame('/freelancer/forum#post-5', $this->notification->getLink());
    }

    public function testSetLinkToNull(): void
    {
        $this->notification->setLink('/some/path');
        $this->notification->setLink(null);
        $this->assertNull($this->notification->getLink());
    }

    public function testSetLinkReturnsFluentInterface(): void
    {
        $this->assertSame($this->notification, $this->notification->setLink('/path'));
    }

    public function testSetIsReadToTrue(): void
    {
        $result = $this->notification->setIsRead(true);

        $this->assertTrue($this->notification->isRead());
        $this->assertInstanceOf(Notification::class, $result);
    }

    public function testSetIsReadToFalse(): void
    {
        $this->notification->setIsRead(true);
        $this->notification->setIsRead(false);
        $this->assertFalse($this->notification->isRead());
    }

    public function testSetIsReadReturnsFluentInterface(): void
    {
        $this->assertSame($this->notification, $this->notification->setIsRead(true));
    }

    public function testIsReadDefaultsToFalse(): void
    {
        $fresh = new Notification();
        $this->assertFalse($fresh->isRead());
    }

    public function testFluentChaining(): void
    {
        $result = $this->notification
            ->setUserId(5)
            ->setType('COMMENT')
            ->setContent('New comment on your post.')
            ->setLink('/freelancer/forum#post-3')
            ->setIsRead(false);

        $this->assertSame(5, $this->notification->getUserId());
        $this->assertSame('COMMENT', $this->notification->getType());
        $this->assertSame('New comment on your post.', $this->notification->getContent());
        $this->assertSame('/freelancer/forum#post-3', $this->notification->getLink());
        $this->assertFalse($this->notification->isRead());
        $this->assertInstanceOf(Notification::class, $result);
    }

    public function testTwoNotificationsHaveIndependentState(): void
    {
        $other = new Notification();

        $this->notification->setUserId(1)->setType('LIKE')->setIsRead(true);
        $other->setUserId(2)->setType('MESSAGE')->setIsRead(false);

        $this->assertSame(1, $this->notification->getUserId());
        $this->assertSame(2, $other->getUserId());
        $this->assertTrue($this->notification->isRead());
        $this->assertFalse($other->isRead());
    }
}
