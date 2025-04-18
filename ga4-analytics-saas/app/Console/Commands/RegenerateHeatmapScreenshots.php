<?php

namespace App\Console\Commands;

use App\Models\Heatmap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class RegenerateHeatmapScreenshots extends Command
{
    protected $signature = 'heatmaps:regenerate-screenshots';
    protected $description = 'Regenerate screenshots for all heatmaps';

    public function handle()
    {
        $heatmaps = Heatmap::whereNull('screenshot_path')->get();

        foreach ($heatmaps as $heatmap) {
            $this->info("Processing heatmap ID: {$heatmap->id}");

            try {
                $screenshot = Heatmap::captureScreenshot($heatmap->page_url);
                if ($screenshot) {
                    $filename = 'heatmaps/' . uniqid() . '.png';
                    Storage::put('public/' . $filename, $screenshot);
                    $heatmap->screenshot_path = $filename;
                    $heatmap->save();

                    $this->info("Screenshot generated successfully");
                }
            } catch (\Exception $e) {
                $this->error("Failed to generate screenshot: " . $e->getMessage());
            }
        }
    }
}
