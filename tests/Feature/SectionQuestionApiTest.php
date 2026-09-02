<?php

use App\Models\Question;
use App\Models\Section;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->moderatorKey = config('game.moderator_secret', 'moderator-secret-key-12345');
});

test('can retrieve sections and ordered questions', function () {
    $section1 = Section::create(['title' => 'Section 1', 'order' => 1, 'is_active' => true]);
    $section2 = Section::create(['title' => 'Section 2', 'order' => 2, 'is_active' => true]);

    $q1 = Question::create(['section_id' => $section1->id, 'question_text' => 'First Q', 'points' => 100, 'order' => 1]);
    $q2 = Question::create(['section_id' => $section1->id, 'question_text' => 'Second Q', 'points' => 200, 'order' => 2]);

    $response = $this->getJson('/api/sections');
    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.title', 'Section 1')
        ->assertJsonPath('data.0.questions_count', 2);

    $questionsResponse = $this->getJson("/api/sections/{$section1->id}/questions");
    $questionsResponse->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.question_text', 'First Q')
        ->assertJsonPath('data.1.question_text', 'Second Q');
});

test('moderator can create section and questions', function () {
    $sectionResponse = $this->withHeader('X-Moderator-Key', $this->moderatorKey)
        ->postJson('/api/sections', [
            'title' => 'Custom Section',
            'description' => 'A newly added category',
            'order' => 5,
        ]);

    $sectionResponse->assertCreated()
        ->assertJsonPath('data.title', 'Custom Section')
        ->assertJsonPath('data.order', 5);

    $sectionId = $sectionResponse->json('data.id');

    $questionResponse = $this->withHeader('X-Moderator-Key', $this->moderatorKey)
        ->postJson("/api/sections/{$sectionId}/questions", [
            'question_text' => 'Who invented the World Wide Web?',
            'answer' => 'Tim Berners-Lee',
            'points' => 300,
        ]);

    $questionResponse->assertCreated()
        ->assertJsonPath('data.question_text', 'Who invented the World Wide Web?')
        ->assertJsonPath('data.points', 300);
});
