<?php

namespace Database\Factories;

use App\Models\Song;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Song> */
class SongFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->words(3, true),
            'artist' => fake()->name(),
            'category' => 'traditional',
            'file_path' => 'songs/'.Str::uuid().'.mp3',
            'duration_seconds' => fake()->numberBetween(90, 240),
            'license_status' => 'original',
            'is_active' => true,
        ];
    }
}
