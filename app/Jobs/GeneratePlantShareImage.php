<?php

namespace App\Jobs;

use App\Models\Plant;
use App\Services\ShareImageService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Renders a single plant's share image off the request path. Dispatched by
 * OgImage when a plant page is shared and its cached PNG is missing or stale.
 */
class GeneratePlantShareImage implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 300;

    public function __construct(public int $plantId) {}

    public function uniqueId(): string
    {
        return (string) $this->plantId;
    }

    public function handle(): void
    {
        $plant = Plant::find($this->plantId);
        if ($plant) {
            ShareImageService::plant($plant);
        }
    }
}
