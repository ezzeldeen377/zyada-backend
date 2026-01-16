<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Review;

class BackfillReviewRatings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reviews:backfill';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill detailed ratings for old reviews using the main rating value.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting backfill of detailed ratings...');

        $count = Review::where('quality_rating', 0)->count();
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        Review::where('quality_rating', 0)->orWhereNull('quality_rating')->chunk(100, function ($reviews) use ($bar) {
            foreach ($reviews as $review) {
                // Use the existing main rating as the detailed rating
                $rating = $review->rating ?? 5; // Default to 5 if somehow null
                
                $review->quality_rating = $rating;
                $review->value_rating = $rating;
                $review->packaging_rating = $rating;
                $review->service_rating = $rating;
                $review->usability_rating = $rating;
                $review->save();
                
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info('Backfill completed successfully.');

        return 0;
    }
}
