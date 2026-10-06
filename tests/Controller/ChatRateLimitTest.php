<?php

namespace App\Tests\Controller;

use App\Controller\ChatController;
use App\Service\PortfolioKnowledge;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ChatRateLimitTest extends TestCase
{
    private FilesystemAdapter $cache;
    private ChatController $controller;
    private Request $request;

    protected function setUp(): void
    {
        $this->cache = new FilesystemAdapter('chat-test-' . bin2hex(random_bytes(6)), 0, dirname(__DIR__, 2) . '/var/test-cache');
        $this->controller = new ChatController(
            $this->createMock(HttpClientInterface::class),
            new PortfolioKnowledge(),
            $this->cache,
            new NullLogger(),
            'test-key',
            'test-model',
        );
        $this->request = Request::create('/api/chat', 'POST', server: ['REMOTE_ADDR' => '192.0.2.1']);
    }

    protected function tearDown(): void
    {
        $this->cache->clear();
    }

    public function testAllowsFifteenRequestsThenBlocks(): void
    {
        for ($i = 0; $i < 15; ++$i) {
            self::assertTrue($this->allow());
        }
        self::assertFalse($this->allow());
    }

    public function testUpdatesKeepOriginalDeadlineAndExpire(): void
    {
        $deadline = time() + 2;
        $item = $this->cache->getItem($this->key());
        $item->set(['count' => 13, 'expiresAt' => $deadline]);
        $item->expiresAt(new \DateTimeImmutable('@' . $deadline));
        $this->cache->save($item);

        self::assertTrue($this->allow());
        self::assertTrue($this->allow());
        self::assertFalse($this->allow());
        self::assertSame($deadline, $this->cache->getItem($this->key())->get()['expiresAt']);

        sleep(2);
        self::assertFalse($this->cache->hasItem($this->key()), 'Saved counter must actually expire in the filesystem cache.');
        self::assertTrue($this->allow());
        self::assertSame(1, $this->cache->getItem($this->key())->get()['count']);
    }

    public function testLegacyPermanentCounterStartsFreshWindow(): void
    {
        $item = $this->cache->getItem($this->key());
        $item->set(15);
        $this->cache->save($item);

        self::assertTrue($this->allow());
        self::assertSame(1, $this->cache->getItem($this->key())->get()['count']);
    }

    private function key(): string
    {
        return 'chat_rl_' . sha1('192.0.2.1');
    }

    private function allow(): bool
    {
        return (new \ReflectionMethod(ChatController::class, 'underRateLimit'))->invoke($this->controller, $this->request);
    }
}
