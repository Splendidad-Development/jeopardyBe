<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    protected $model = Question::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'section_id' => Section::factory(),
            'question_text' => fake()->sentence().'?',
            'answer' => fake()->word(),
            'points' => 100,
            'order' => 1,
        ];
    }
}
