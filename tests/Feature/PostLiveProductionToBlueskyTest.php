<?php

use App\Models\Plant;
use App\Models\Reactor;
use App\Models\Record;
use App\Services\SocialShotService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/** A SocialShotService stub that returns real (tiny) image files, no Browsershot. */
class FakeSocialShotService extends SocialShotService
{
    public function home(): ?string
    {
        return $this->dummy('home');
    }

    public function tableau(): ?string
    {
        return $this->dummy('tableau');
    }

    public function plant(Plant $plant): ?string
    {
        return $this->dummy('plant-'.$plant->slug);
    }

    protected function dummy(string $name): string
    {
        $path = sys_get_temp_dir().'/bsky-test-'.$name.'.png';
        file_put_contents($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));

        return $path;
    }
}

function seedFleetWithMovers(): void
{
    // Two plants with two hourly buckets each → two non-zero movers.
    $a = Plant::factory()->create(['name' => 'Golfech', 'slug' => 'golfech']);
    $ra = Reactor::factory()->for($a)->create(['reactor_index' => 1, 'net_power_mw' => 1300]);
    Record::factory()->for($ra)->create(['value' => 800, 'date' => now()->subHour()]);
    Record::factory()->for($ra)->create(['value' => 1200, 'date' => now()]);   // +400

    $b = Plant::factory()->create(['name' => 'Gravelines', 'slug' => 'gravelines']);
    $rb = Reactor::factory()->for($b)->create(['reactor_index' => 1, 'net_power_mw' => 900]);
    Record::factory()->for($rb)->create(['value' => 900, 'date' => now()->subHour()]);
    Record::factory()->for($rb)->create(['value' => 500, 'date' => now()]);    // -400
}

function fakeBluesky(): void
{
    Http::fake([
        '*com.atproto.server.createSession' => Http::response(['accessJwt' => 'jwt-123', 'did' => 'did:plc:test']),
        '*com.atproto.repo.uploadBlob' => Http::response(['blob' => ['$type' => 'blob', 'ref' => ['$link' => 'abc']]]),
        '*com.atproto.repo.createRecord' => Http::response(['uri' => 'at://did:plc:test/app.bsky.feed.post/xyz']),
    ]);
}

it('posts the national card plus map and table screenshots with alt text and a link facet', function () {
    config()->set('services.bluesky', [
        'identifier' => 'bot.example.com',
        'password' => 'app-pass',
        'base_url' => 'https://bsky.social',
    ]);
    seedFleetWithMovers();
    fakeBluesky();
    app()->instance(SocialShotService::class, new FakeSocialShotService);

    // Provide the national @2x card on disk (render is skipped under tests); back up any real one.
    $card = storage_path('app/public/og/national@2x.png');
    $existed = is_file($card);
    $backup = $existed ? file_get_contents($card) : null;
    @mkdir(dirname($card), 0755, true);
    file_put_contents($card, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));

    try {
        $this->artisan('app:post-live-production-to-bluesky')->assertSuccessful();
    } finally {
        $existed ? file_put_contents($card, $backup) : @unlink($card);
    }

    Http::assertSent(fn (Request $r) => str_contains($r->url(), 'com.atproto.server.createSession'));

    Http::assertSent(function (Request $r) {
        if (! str_contains($r->url(), 'com.atproto.repo.createRecord')) {
            return false;
        }

        $record = $r['record'];
        $text = $record['text'];
        $images = $record['embed']['images'];

        // Link facet byte-slice must equal exactly "electronucleaire.fr".
        $facet = $record['facets'][0];
        $slice = substr($text, $facet['index']['byteStart'], $facet['index']['byteEnd'] - $facet['index']['byteStart']);

        return $record['embed']['$type'] === 'app.bsky.embed.images'
            && count($images) === 3                                    // national card + map + table
            && collect($images)->every(fn ($i) => $i['alt'] !== '' && isset($i['aspectRatio']['width']))
            && str_contains($text, 'GW')
            && str_contains($text, 'Plus fortes variations')
            && $slice === 'electronucleaire.fr'
            && $facet['features'][0]['uri'] === 'https://electronucleaire.fr';
    });
});

it('fails cleanly when credentials are missing and never hits the network', function () {
    config()->set('services.bluesky', ['identifier' => null, 'password' => null, 'base_url' => 'https://bsky.social']);
    seedFleetWithMovers();
    Http::fake();
    app()->instance(SocialShotService::class, new FakeSocialShotService);

    $this->artisan('app:post-live-production-to-bluesky')->assertFailed();

    Http::assertNothingSent();
});
