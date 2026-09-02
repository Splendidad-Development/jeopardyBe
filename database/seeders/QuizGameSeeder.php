<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\Section;
use App\Models\Team;
use Illuminate\Database\Seeder;

class QuizGameSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Default Teams
        Team::firstOrCreate(['name' => 'Team 1'], ['color_code' => '#2563eb']);
        Team::firstOrCreate(['name' => 'Team 2'], ['color_code' => '#dc2626']);

        // 2. Create 4 Game Sections with Configurable Questions
        $sectionsData = [
            [
                'title' => 'Section 1: General Knowledge',
                'description' => 'Test your basic trivia knowledge across varied domains.',
                'order' => 1,
                'questions' => [
                    ['question_text' => 'What is the capital of France?', 'answer' => 'Paris', 'points' => 100],
                    ['question_text' => 'Which planet is known as the Red Planet?', 'answer' => 'Mars', 'points' => 200],
                    ['question_text' => 'What is the largest mammal in the world?', 'answer' => 'Blue Whale', 'points' => 300],
                    ['question_text' => 'In which year did the Titanic sink?', 'answer' => '1912', 'points' => 400],
                    ['question_text' => 'What is the hardest natural substance on Earth?', 'answer' => 'Diamond', 'points' => 500],
                ],
            ],
            [
                'title' => 'Section 2: Science & Tech',
                'description' => 'Innovations, physics, biology, and computing history.',
                'order' => 2,
                'questions' => [
                    ['question_text' => 'What does CPU stand for in computers?', 'answer' => 'Central Processing Unit', 'points' => 100],
                    ['question_text' => 'What gas do plants absorb from the atmosphere?', 'answer' => 'Carbon Dioxide', 'points' => 200],
                    ['question_text' => 'Who developed the theory of relativity?', 'answer' => 'Albert Einstein', 'points' => 300],
                    ['question_text' => 'What is the chemical symbol for Gold?', 'answer' => 'Au', 'points' => 400],
                    ['question_text' => 'How many bones are in the adult human body?', 'answer' => '206', 'points' => 500],
                ],
            ],
            [
                'title' => 'Section 3: History & Geography',
                'description' => 'Continents, oceans, world wonders, and major milestones.',
                'order' => 3,
                'questions' => [
                    ['question_text' => 'Which is the longest river in the world?', 'answer' => 'Nile River', 'points' => 100],
                    ['question_text' => 'In which country are the Great Pyramids of Giza located?', 'answer' => 'Egypt', 'points' => 200],
                    ['question_text' => 'Which empire built the Colosseum in Rome?', 'answer' => 'Roman Empire', 'points' => 300],
                    ['question_text' => 'What is the smallest country in the world by area?', 'answer' => 'Vatican City', 'points' => 400],
                    ['question_text' => 'Who was the first person to step on the Moon?', 'answer' => 'Neil Armstrong', 'points' => 500],
                ],
            ],
            [
                'title' => 'Section 4: Pop Culture & Entertainment',
                'description' => 'Cinema, music, iconic legends, and gaming lore.',
                'order' => 4,
                'questions' => [
                    ['question_text' => 'Who is known as the "King of Pop"?', 'answer' => 'Michael Jackson', 'points' => 100],
                    ['question_text' => 'Which fictional school did Harry Potter attend?', 'answer' => 'Hogwarts', 'points' => 200],
                    ['question_text' => 'What is the name of Mario\'s brother in Nintendo games?', 'answer' => 'Luigi', 'points' => 300],
                    ['question_text' => 'Which movie won the first Academy Award for Best Animated Feature in 2002?', 'answer' => 'Shrek', 'points' => 400],
                    ['question_text' => 'Who painted the Mona Lisa?', 'answer' => 'Leonardo da Vinci', 'points' => 500],
                ],
            ],
        ];

        foreach ($sectionsData as $sectionData) {
            $questions = $sectionData['questions'];
            unset($sectionData['questions']);

            $section = Section::updateOrCreate(
                ['title' => $sectionData['title']],
                $sectionData
            );

            foreach ($questions as $qIndex => $questionData) {
                Question::updateOrCreate(
                    [
                        'section_id' => $section->id,
                        'order' => $qIndex + 1,
                    ],
                    [
                        'question_text' => $questionData['question_text'],
                        'answer' => $questionData['answer'],
                        'points' => $questionData['points'],
                    ]
                );
            }
        }
    }
}
