<?php

use App\Models\Question;
use App\Models\Section;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->section1 = Section::create(['title' => 'Section 1', 'order' => 1, 'is_active' => true]);
    $this->q1_1 = Question::create(['section_id' => $this->section1->id, 'question_text' => 'Q1.1', 'points' => 100, 'order' => 1]);
    $this->q1_2 = Question::create(['section_id' => $this->section1->id, 'question_text' => 'Q1.2', 'points' => 100, 'order' => 2]);

    $this->section2 = Section::create(['title' => 'Section 2', 'order' => 2, 'is_active' => true]);
    $this->q2_1 = Question::create(['section_id' => $this->section2->id, 'question_text' => 'Q2.1', 'points' => 100, 'order' => 1]);
});

test('can create a game session with 2 teams in not_started status', function () {
    $response = $this->postJson('/api/games', [
        'team1_name' => 'Alpha',
        'team2_name' => 'Bravo',
        'timer_duration_seconds' => 30,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.status', 'not_started')
        ->assertJsonPath('data.timer.duration_seconds', 30)
        ->assertJsonCount(2, 'data.teams')
        ->assertJsonPath('data.teams.0.name', 'Alpha')
        ->assertJsonPath('data.teams.1.name', 'Bravo');
});

test('starting a game assigns section 1, question 1, and team 1 with active timer', function () {
    $gameResponse = $this->postJson('/api/games');
    $gameId = $gameResponse->json('data.id');

    $startResponse = $this->postJson("/api/games/{$gameId}/start");

    $startResponse->assertOk()
        ->assertJsonPath('data.status', 'in_progress')
        ->assertJsonPath('data.current_section.id', $this->section1->id)
        ->assertJsonPath('data.current_question.id', $this->q1_1->id)
        ->assertJsonPath('data.current_question_number', 1)
        ->assertJsonPath('data.teams.0.id', $startResponse->json('data.current_team.id'));

    // Verify current-question endpoint
    $currentQResponse = $this->getJson("/api/games/{$gameId}/current-question");
    $currentQResponse->assertOk()
        ->assertJsonPath('status', 'in_progress')
        ->assertJsonPath('data.question.id', $this->q1_1->id)
        ->assertJsonPath('data.assigned_team.id', $startResponse->json('data.current_team.id'))
        ->assertJsonPath('data.timer.duration_seconds', 30);
});

test('next-question alternates turns between team 1 and team 2', function () {
    $gameResponse = $this->postJson('/api/games', [
        'team1_name' => 'Team 1',
        'team2_name' => 'Team 2',
    ]);
    $gameId = $gameResponse->json('data.id');
    $team1Id = $gameResponse->json('data.teams.0.id');
    $team2Id = $gameResponse->json('data.teams.1.id');

    // Start game -> Question 1 assigned to Team 1
    $this->postJson("/api/games/{$gameId}/start")
        ->assertJsonPath('data.current_team.id', $team1Id)
        ->assertJsonPath('data.current_question.id', $this->q1_1->id);

    // Advance -> Question 2 assigned to Team 2
    $this->postJson("/api/games/{$gameId}/next-question")
        ->assertOk()
        ->assertJsonPath('data.current_team.id', $team2Id)
        ->assertJsonPath('data.current_question.id', $this->q1_2->id)
        ->assertJsonPath('data.current_question_number', 2);

    // Advance -> Section 2 Question 1 assigned back to Team 1
    $this->postJson("/api/games/{$gameId}/next-question")
        ->assertOk()
        ->assertJsonPath('data.current_team.id', $team1Id)
        ->assertJsonPath('data.current_section.id', $this->section2->id)
        ->assertJsonPath('data.current_question.id', $this->q2_1->id)
        ->assertJsonPath('data.current_question_number', 1);

    // Advance after all questions finished -> game status becomes completed
    $completeResponse = $this->postJson("/api/games/{$gameId}/next-question");
    $completeResponse->assertOk()
        ->assertJsonPath('data.status', 'completed')
        ->assertJsonPath('data.is_completed', true);
});

test('cannot advance question if game is not in progress', function () {
    $gameResponse = $this->postJson('/api/games');
    $gameId = $gameResponse->json('data.id');

    $response = $this->postJson("/api/games/{$gameId}/next-question");
    $response->assertStatus(422)
        ->assertJsonFragment(['message' => 'Cannot advance question: Game session is not in progress (current status: not_started).']);
});
