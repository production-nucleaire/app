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
     * Post text (with up to 4 images) and return the created record URI.
     *
     * @param  array<int,array{path: string, alt?: string}>  $images  Each has a file path and optional alt text.
     * @param  array<int,array<string,mixed>>  $facets  Rich-text facets (links, mentions, tags).
     */
    public function post(string $text, array $images = [], array $facets = []): string
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

        $embedImages = $this->buildImageEmbeds($accessJwt, $images);
        if ($embedImages !== []) {
            $record['embed'] = [
                '$type' => 'app.bsky.embed.images',
                'images' => $embedImages,
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
     * Upload each image (capped at Bluesky's 4-per-post limit) and build the
     * app.bsky.embed.images entries, deriving mime + aspect ratio from the file.
     *
     * @param  array<int,array{path: string, alt?: string}>  $images
     * @return array<int,array<string,mixed>>
     */
    protected function buildImageEmbeds(string $accessJwt, array $images): array
    {
        $embeds = [];

        foreach (array_slice($images, 0, 4) as $image) {
            $path = $image['path'] ?? null;
            if ($path === null || ! is_file($path)) {
                continue;
            }

            $size = @getimagesize($path);
            $mime = $size['mime'] ?? 'image/png';

            $blob = $this->uploadBlob($accessJwt, (string) file_get_contents($path), $mime);

            $entry = [
                'alt' => $image['alt'] ?? '',
                'image' => $blob,
            ];
            if ($size !== false) {
                $entry['aspectRatio'] = ['width' => (int) $size[0], 'height' => (int) $size[1]];
            }

            $embeds[] = $entry;
        }

        return $embeds;
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
