<?php

namespace App\Controller;

use App\Service\PortfolioKnowledge;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ChatController extends AbstractController
{
    private const MAX_MESSAGE_LENGTH = 1000;
    private const MAX_HISTORY_TURNS = 8;
    private const RATE_LIMIT = 15;        // requests
    private const RATE_WINDOW = 60;       // seconds

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly PortfolioKnowledge $knowledge,
        private readonly CacheItemPoolInterface $cache,
        private readonly LoggerInterface $logger,
        #[Autowire('%env(GEMINI_API_KEY)%')] private readonly string $apiKey,
        #[Autowire('%env(GEMINI_MODEL)%')] private readonly string $model,
    ) {
    }

    #[Route('/api/chat', name: 'app_chat', methods: ['POST'])]
    public function chat(Request $request): JsonResponse
    {
        if ($this->apiKey === '') {
            return $this->json(['error' => 'assistant_unconfigured'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        if (!$this->underRateLimit($request)) {
            return $this->json(['error' => 'rate_limited'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $payload = json_decode($request->getContent(), true);
        $message = is_array($payload) ? trim((string) ($payload['message'] ?? '')) : '';

        if ($message === '') {
            return $this->json(['error' => 'empty_message'], Response::HTTP_BAD_REQUEST);
        }
        if (mb_strlen($message) > self::MAX_MESSAGE_LENGTH) {
            return $this->json(['error' => 'message_too_long'], Response::HTTP_BAD_REQUEST);
        }

        $contents = $this->buildContents($payload['history'] ?? [], $message);

        try {
            $response = $this->httpClient->request(
                'POST',
                sprintf('https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent', $this->model),
                [
                    'headers' => [
                        'x-goog-api-key' => $this->apiKey,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'systemInstruction' => [
                            'parts' => [['text' => $this->knowledge->systemInstruction()]],
                        ],
                        'contents' => $contents,
                        'generationConfig' => [
                            'temperature' => 0.4,
                            'maxOutputTokens' => 600,
                        ],
                    ],
                    'timeout' => 30,
                ],
            );

            $data = $response->toArray(false);
        } catch (HttpExceptionInterface $e) {
            $this->logger->error('Gemini request failed', ['exception' => $e]);

            return $this->json(['error' => 'assistant_unavailable'], Response::HTTP_BAD_GATEWAY);
        }

        $reply = $this->extractReply($data);

        if ($reply === null) {
            $this->logger->warning('Gemini returned no usable text', ['response' => $data]);

            return $this->json(['error' => 'assistant_unavailable'], Response::HTTP_BAD_GATEWAY);
        }

        return $this->json(['reply' => $reply]);
    }

    /**
     * Build the Gemini `contents` array from prior turns plus the new message.
     *
     * @param mixed $history
     * @return array<int, array{role: string, parts: array<int, array{text: string}>}>
     */
    private function buildContents(mixed $history, string $message): array
    {
        $contents = [];

        if (is_array($history)) {
            foreach (array_slice($history, -2 * self::MAX_HISTORY_TURNS) as $turn) {
                if (!is_array($turn)) {
                    continue;
                }
                $role = ($turn['role'] ?? '') === 'model' ? 'model' : 'user';
                $text = trim((string) ($turn['text'] ?? ''));
                if ($text === '') {
                    continue;
                }
                $contents[] = ['role' => $role, 'parts' => [['text' => mb_substr($text, 0, self::MAX_MESSAGE_LENGTH)]]];
            }
        }

        $contents[] = ['role' => 'user', 'parts' => [['text' => $message]]];

        return $contents;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function extractReply(array $data): ?string
    {
        $parts = $data['candidates'][0]['content']['parts'] ?? null;
        if (!is_array($parts)) {
            return null;
        }

        $text = '';
        foreach ($parts as $part) {
            if (isset($part['text'])) {
                $text .= $part['text'];
            }
        }

        $text = trim($text);

        return $text === '' ? null : $text;
    }

    private function underRateLimit(Request $request): bool
    {
        $key = 'chat_rl_' . sha1((string) $request->getClientIp());
        $item = $this->cache->getItem($key);

        $count = $item->isHit() ? (int) $item->get() : 0;
        if ($count >= self::RATE_LIMIT) {
            return false;
        }

        if (!$item->isHit()) {
            $item->expiresAfter(self::RATE_WINDOW);
        }
        $item->set($count + 1);
        $this->cache->save($item);

        return true;
    }
}
