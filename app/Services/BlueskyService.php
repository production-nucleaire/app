<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Minimal AT Protocol (Bluesky) XRPC client built on Laravel's HTTP client.
 *
 * Posting is infrequent (at most hourly), so we create a fresh session per post
 * rather than caching/refreshing access tokens. Credentials come from
 * config('services.bluesky') — use a Bluesky app-password, never the account one.
 */
class BlueskyService
{
    protected string $baseUrl;

    protected ?string $identifier;

    protected ?string $password;

    public function __construct()
    {
        $cfg = config('services.bluesky');
        $this->baseUrl = rtrim($cfg['base_url'] ?? 'https://bsky.social', '/');
        $this->identifier = $cfg['identifier'] ?? null;
        $this->password = $cfg['password'] ?? null;
    }

    /**
     * Post text (with an optional image) and return the created record URI.
     *
     * @param  array<int,array<string,mixed>>  $facets  Rich-text facets (links, mentions, tags).
     */
    public function post(string $text, ?string $imagePath = null, ?string $altText = null, array $facets = []): string
    {
        $session = $this->createSession();
        $accessJwt = $session['accessJwt'];
        $did = $session['did'];

        $record = [
            '$type' => 'app.bsky.feed.post',
            'text' => $text,
            'createdAt' => now()->toIso8601ZuluString(),
            'langs' => ['fr'],
        ];

        if ($facets !== []) {
            $record['facets'] = $facets;
        }

        if ($imagePath !== null && is_file($imagePath)) {
            $blob = $this->uploadBlob($accessJwt, (string) file_get_contents($imagePath), 'image/png');
            $record['embed'] = [
                '$type' => 'app.bsky.embed.images',
                'images' => [[
                    'alt' => $altText ?? '',
                    'image' => $blob,
                    'aspectRatio' => ['width' => 1200, 'height' => 630],
                ]],
            ];
        }

        $response = Http::withToken($accessJwt)
            ->post($this->baseUrl.'/xrpc/com.atproto.repo.createRecord', [
                'repo' => $did,
                'collection' => 'app.bsky.feed.post',
                'record' => $record,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Bluesky createRecord failed: '.$response->body());
        }

        return (string) $response->json('uri');
    }

    /**
     * Authenticate and return the session payload (accessJwt, did, handle, ...).
     *
     * @return array<string,mixed>
     */
    public function createSession(): array
    {
        if (empty($this->identifier) || empty($this->password)) {
            throw new RuntimeException('Bluesky credentials are not configured (BLUESKY_IDENTIFIER / BLUESKY_PASSWORD).');
        }

        $response = Http::post($this->baseUrl.'/xrpc/com.atproto.server.createSession', [
            'identifier' => $this->identifier,
            'password' => $this->password,
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Bluesky createSession failed: '.$response->body());
        }

        return $response->json();
    }

    /**
     * Upload raw image bytes and return the resulting blob reference.
     *
     * @return array<string,mixed>
     */
    public function uploadBlob(string $accessJwt, string $bytes, string $mime = 'image/png'): array
    {
        $response = Http::withToken($accessJwt)
            ->withBody($bytes, $mime)
            ->post($this->baseUrl.'/xrpc/com.atproto.repo.uploadBlob');

        if ($response->failed()) {
            throw new RuntimeException('Bluesky uploadBlob failed: '.$response->body());
        }

        return $response->json('blob');
    }
}
