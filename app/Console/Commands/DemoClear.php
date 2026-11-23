<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\BlogPost;
use App\Models\Developer;
use App\Models\Location;
use App\Models\Media;
use App\Models\Project;
use App\Models\Property;
use App\Models\SlugHistory;
use App\Models\Verification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** Removes everything DemoSeeder created so a preview server can switch to real content. Real records are never touched. */
class DemoClear extends Command
{
    protected $signature = 'pcbd:demo-clear {--force : Do not ask for confirmation}';

    protected $description = 'Delete all demo content (listings, developers, blog posts, artwork, demo areas).';

    private const DEMO_LOCATIONS = ['Gulshan', 'Banani', 'Dhanmondi', 'Uttara', 'Bashundhara R/A', 'Mirpur', 'Khulshi', 'Agrabad', 'Dhaka', 'Chattogram'];

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('Permanently delete all demo content (items with demo artwork, "Demo ..." developers)?')) {
            return self::FAILURE;
        }

        DB::transaction(function (): void {
            $demoIds = fn (string $type) => Media::where('is_demo', true)->where('mediable_type', (new $type)->getMorphClass())->pluck('mediable_id');

            $properties = Property::withTrashed()->whereIn('id', $demoIds(Property::class))->get();
            $projects = Project::withTrashed()->whereIn('id', $demoIds(Project::class))->get();
            $developers = Developer::withTrashed()->where(fn ($q) => $q->whereIn('id', $demoIds(Developer::class))->orWhere('name', 'like', 'Demo %'))->get();
            $posts = BlogPost::withTrashed()->whereIn('id', $demoIds(BlogPost::class))->get();

            // Children first: properties and projects reference developers/locations with RESTRICT.
            foreach ([$properties, $projects, $developers, $posts] as $set) {
                foreach ($set as $model) {
                    Verification::where('verifiable_type', $model->getMorphClass())->where('verifiable_id', $model->id)->delete();
                    SlugHistory::where('sluggable_type', $model->getMorphClass())->where('sluggable_id', $model->id)->delete();
                    Media::where('mediable_type', $model->getMorphClass())->where('mediable_id', $model->id)->get()->each->delete();
                    $model->forceDelete();
                }
            }

            // Demo areas/cities only when nothing real hangs off them any more.
            foreach ([0, 1] as $_) {
                Location::whereIn('name', self::DEMO_LOCATIONS)
                    ->whereDoesntHave('children')->whereDoesntHave('properties')->whereDoesntHave('projects')
                    ->get()->each->delete();
            }
        });

        Cache::forget('pcbd.demo-active');
        Cache::forget('pcbd.sitemap');
        $this->components->info('Demo content removed.');

        return self::SUCCESS;
    }
}
