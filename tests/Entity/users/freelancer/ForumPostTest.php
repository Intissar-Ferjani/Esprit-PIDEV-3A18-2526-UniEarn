<?php

namespace App\Tests\Entity\users\freelancer;

use App\Entity\users\freelancer\ForumPost;
use PHPUnit\Framework\TestCase;

class ForumPostTest extends TestCase
{
    private ForumPost $post;

    protected function setUp(): void
    {
        $this->post = new ForumPost();
    }

    public function testDefaultValues(): void
    {
        $this->assertSame('', $this->post->getTitle());
        $this->assertSame('', $this->post->getContent());
        $this->assertSame('General', $this->post->getCategory());
        $this->assertSame(0, $this->post->getViews());
        $this->assertNull($this->post->getPostId());
        $this->assertNull($this->post->getFreelancerId());
        $this->assertNull($this->post->getGifUrl());
    }

    public function testCreatedAtIsSetOnConstruction(): void
    {
        $before = new \DateTime('-1 second');
        $after  = new \DateTime('+1 second');

        $this->assertNotNull($this->post->getCreatedAt());
        $this->assertGreaterThan($before, $this->post->getCreatedAt());
        $this->assertLessThan($after, $this->post->getCreatedAt());
    }

    public function testUpdatedAtIsSetOnConstruction(): void
    {
        $this->assertNotNull($this->post->getUpdatedAt());
    }

    public function testSetAndGetTitle(): void
    {
        $result = $this->post->setTitle('How to use UniEarn?');

        $this->assertSame('How to use UniEarn?', $this->post->getTitle());
        $this->assertInstanceOf(ForumPost::class, $result);
    }

    public function testSetTitleReturnsFluentInterface(): void
    {
        $this->assertSame($this->post, $this->post->setTitle('Test'));
    }

    public function testSetAndGetContent(): void
    {
        $this->post->setContent('This is a detailed post about freelancing.');
        $this->assertSame('This is a detailed post about freelancing.', $this->post->getContent());
    }

    public function testSetContentReturnsFluentInterface(): void
    {
        $this->assertSame($this->post, $this->post->setContent('content'));
    }

    public function testSetAndGetCategory(): void
    {
        foreach (['Technology', 'Design', 'Business', 'Career', 'General'] as $cat) {
            $this->post->setCategory($cat);
            $this->assertSame($cat, $this->post->getCategory());
        }
    }

    public function testSetCategoryReturnsFluentInterface(): void
    {
        $this->assertSame($this->post, $this->post->setCategory('Design'));
    }

    public function testSetAndGetFreelancerId(): void
    {
        $this->post->setFreelancerId(99);
        $this->assertSame(99, $this->post->getFreelancerId());
    }

    public function testSetFreelancerIdToNull(): void
    {
        $this->post->setFreelancerId(5);
        $this->post->setFreelancerId(null);
        $this->assertNull($this->post->getFreelancerId());
    }

    public function testSetAndGetGifUrl(): void
    {
        $this->post->setGifUrl('https://media.giphy.com/example.gif');
        $this->assertSame('https://media.giphy.com/example.gif', $this->post->getGifUrl());
    }

    public function testSetGifUrlToNull(): void
    {
        $this->post->setGifUrl('https://example.com/gif.gif');
        $this->post->setGifUrl(null);
        $this->assertNull($this->post->getGifUrl());
    }

    public function testSetAndGetViews(): void
    {
        $this->post->setViews(150);
        $this->assertSame(150, $this->post->getViews());
    }

    public function testSetViewsReturnsFluentInterface(): void
    {
        $this->assertSame($this->post, $this->post->setViews(10));
    }

    public function testIncrementViews(): void
    {
        $this->post->setViews(5);
        $this->post->incrementViews();
        $this->assertSame(6, $this->post->getViews());
    }

    public function testIncrementViewsMultipleTimes(): void
    {
        $this->assertSame(0, $this->post->getViews());
        $this->post->incrementViews();
        $this->post->incrementViews();
        $this->post->incrementViews();
        $this->assertSame(3, $this->post->getViews());
    }

    public function testIncrementViewsReturnsFluentInterface(): void
    {
        $this->assertSame($this->post, $this->post->incrementViews());
    }

    public function testSetAndGetCreatedAt(): void
    {
        $dt = new \DateTime('2025-01-15 10:00:00');
        $this->post->setCreatedAt($dt);
        $this->assertSame($dt, $this->post->getCreatedAt());
    }

    public function testSetCreatedAtReturnsFluentInterface(): void
    {
        $this->assertSame($this->post, $this->post->setCreatedAt(new \DateTime()));
    }

    public function testSetAndGetUpdatedAt(): void
    {
        $dt = new \DateTime('2025-06-01 12:00:00');
        $this->post->setUpdatedAt($dt);
        $this->assertSame($dt, $this->post->getUpdatedAt());
    }

    public function testSetUpdatedAtToNull(): void
    {
        $this->post->setUpdatedAt(null);
        $this->assertNull($this->post->getUpdatedAt());
    }

    public function testSetAndGetPostId(): void
    {
        $this->post->setPostId(42);
        $this->assertSame(42, $this->post->getPostId());
    }

    public function testTwoPostsHaveIndependentState(): void
    {
        $other = new ForumPost();
        $this->post->setTitle('First Post')->setCategory('Technology')->setViews(10);
        $other->setTitle('Second Post')->setCategory('Design')->setViews(99);

        $this->assertSame('First Post', $this->post->getTitle());
        $this->assertSame('Second Post', $other->getTitle());
        $this->assertNotSame($this->post->getViews(), $other->getViews());
    }
}
