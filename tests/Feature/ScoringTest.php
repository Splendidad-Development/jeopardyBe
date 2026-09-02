<?php

use App\Models\Question;
use App\Models\Section;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->section = Section::create(['title' => 'Section 1', 'order' => 1, 'is_active' => true]);
    $this->question = Question::create(['section_id' => $this->section->id, 'question_text' => 'Q1', 'points' => 100, 'order' => 1]);

    $this->moderatorKey = config('game.moderator_secret', 'moderator-secret-key-12345');
});

test('unauthorized requests cannot modify scores', function () {
    $gameResponse = $this->postJson('/api/games');
    $gameId = $gameResponse->json('data.id');

    // Without X-Moderator-Key header
    $response = $this->putJson("/api/games/{$gameId}/scores", [
        'team1_score' => 200,
    ]);

    $response->assertUnauthorized();
});

test('moderator can set team scores and retrieve them', function () {
    $gameResponse = $this->postJson('/api/games');
    $gameId = $gameResponse->json('data.id');
    $team1Id = $gameResponse->json('data.teams.0.id');
    $team2Id = $gameResponse->json('data.teams.1.id');

    // Update both teams' scores via moderator header
    $updateResponse = $this->withHeader('X-Moderator-Key', $this->moderatorKey)
        ->putJson("/api/games/{$gameId}/scores", [
            'team1_score' => 300,
            'team2_score' => 150,
            'reason' => 'End of Section 1 scoring',
        ]);

    $updateResponse->assertOk()
        ->assertJsonPath('scores.0.score', 300)
        ->assertJsonPath('scores.1.score', 150);

    // Retrieve scores
    $scoresResponse = $this->getJson("/api/games/{$gameId}/scores");
    $scoresResponse->assertOk()
        ->assertJsonPath('scores.0.team_id', $team1Id)
        ->assertJsonPath('scores.0.score', 300)
        ->assertJsonPath('scores.1.team_id', $team2Id)
        ->assertJsonPath('scores.1.score', 150);
});

test('moderator can adjust score with positive or negative delta', function () {
    $gameResponse = $this->postJson('/api/games');
    $gameId = $gameResponse->json('data.id');
    $team1Id = $gameResponse->json('data.teams.0.id');

    // Start game
    $this->postJson("/api/games/{$gameId}/start");

    // Add 100 points
    $adjustResponse1 = $this->withHeader('X-Moderator-Key', $this->moderatorKey)
        ->postJson("/api/games/{$gameId}/scores/adjust", [
            'team_id' => $team1Id,
            'delta' => 100,
            'reason' => 'Correct bonus answer',
        ]);

    $adjustResponse1->assertOk()
        ->assertJsonPath('new_score', 100);

    // Deduct 50 points
    $adjustResponse2 = $this->withHeader('X-Moderator-Key', $this->moderatorKey)
        ->postJson("/api/games/{$gameId}/scores/adjust", [
            'team_id' => $team1Id,
            'delta' => -50,
            'reason' => 'Penalty deduction',
        ]);

    $adjustResponse2->assertOk()
        ->assertJsonPath('new_score', 50);

    // Check logs
    $logsResponse = $this->getJson("/api/games/{$gameId}/scores/logs");
    $logsResponse->assertOk()
        ->assertJsonCount(2, 'logs')
        ->assertJsonPath('logs.0.score_change', -50)
        ->assertJsonPath('logs.1.score_change', 100);
});
