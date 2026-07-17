<?php

use App\Models\Plant;
use App\Models\Reactor;
use App\Models\Record;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

function seedNationalFleet(): void
{
    $plant = Plant::factory()->create(['name' => 'Golfech', 'slug' => 'golfech']);
    $r1 = Reactor::factory()->for($plant)->create(['reactor_index' => 1, 'net_power_mw' => 1300]);
    $r2 = Reactor::factory()->for($plant)->create(['reactor_index' => 2, 'net_power_mw' => 1300]);
    Record::factory()->for($r1)->create(['value' => 1200, 'date' => now()]);
    Record::factory()->for($r2)->create(['value' => 0, 'date' => now()]);
}

function fakeBluesky(): void
{
    Http::fake([
        '*com.atproto.server.createSession' => Http::response(['accessJwt' => 'jwt-123', 'did' => 'did:plc:test']),
        '*com.atproto.repo.uploadBlob' => Http::response(['blob' => ['$type' => 'blob', 'ref' => ['$link' => 'abc']]]),
        '*com.atproto.repo.createRecord' => Http::response(['uri' => 'at://did:plc:test/app.bsky.feed.post/xyz']),
    ]);
}

it('posts national production to Bluesky with an image and link facet', function () {
    config()->set('services.bluesky', [
        'identifier' => 'bot.example.com',
        'password' => 'app-pass',
        'base_url' => 'https://bsky.social',
    ]);
    seedNationalFleet();
    fakeBluesky();

    // Provide a real national.png so the command doesn't try to render one with
    // Browsershot; back up and restore any existing dev image.
    $path = storage_path('app/public/og/national.png');
    $existed = is_file($path);
    $backup = $existed ? file_get_contents($path) : null;
    @mkdir(dirname($path), 0755, true);
    file_put_contents($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));

    try {
        $this->artisan('app:post-live-production-to-bluesky')
            ->assertSuccessful();
    } finally {
        if ($existed) {
            file_put_contents($path, $backup);
        } else {
            @unlink($path);
        }
    }

    Http::assertSent(fn (Request $r) => str_contains($r->url(), 'com.atproto.server.createSession'));

    Http::assertSent(function (Request $r) {
        if (! str_contains($r->url(), 'com.atproto.repo.createRecord')) {
            return false;
        }

        $record = $r['record'];
        $text = $record['text'];

        // Link facet byte-slice must equal exactly "electronucleaire.fr".
        $facet = $record['facets'][0];
        $slice = substr($text, $facet['index']['byteStart'], $facet['index']['byteEnd'] - $facet['index']['byteStart']);

        return str_contains($text, 'GW')
            && $record['embed']['$type'] === 'app.bsky.embed.images'
            && $record['embed']['images'][0]['alt'] !== ''
            && $slice === 'electronucleaire.fr'
            && $facet['features'][0]['uri'] === 'https://electronucleaire.fr';
    });
});

it('fails cleanly when credentials are missing and never hits the network', function () {
    config()->set('services.bluesky', ['identifier' => null, 'password' => null, 'base_url' => 'https://bsky.social']);
    seedNationalFleet();
    Http::fake();

    $this->artisan('app:post-live-production-to-bluesky')
        ->assertFailed();

    Http::assertNothingSent();
});
