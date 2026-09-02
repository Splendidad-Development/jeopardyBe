<?php

use App\Models\GameSessionHistory;
use App\Models\Question;
use App\Models\Section;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->section = Section::create(['title' => 'Section 1', 'order' => 1, 'is_active' => true]);
    $this->question = Question::create(['section_id' => $this->section->id, 'question_text' => 'Q1', 'points' => 100, 'order' => 1]);
    $this->moderatorKey = config('game.moderator_secret', 'moderator-secret-key-12345');
});

test('restarting a game resets state and saves history snapshot', function () {
    $gameResponse = $this->postJson('/api/games');
    $gameId = $gameResponse->json('data.id');

    // Start game
    $this->postJson("/api/games/{$gameId}/start");

    // Set some scores
    $this->withHeader('X-Moderator-Key', $this->moderatorKey)
        ->putJson("/api/games/{$gameId}/scores", [
            'team1_score' => 200,
            'team2_score' => 100,
        ]);

    // Restart game
    $restartResponse = $this->postJson("/api/games/{$gameId}/restart", [
        'preserve_history' => true,
    ]);

    $restartResponse->assertOk()
        ->assertJsonPath('data.status', 'not_started')
        ->assertJsonPath('data.current_section', null)
        ->assertJsonPath('data.current_question', null)
        ->assertJsonPath('data.current_team', null)
        ->assertJsonPath('data.current_question_number', 0)
        ->assertJsonPath('data.teams.0.score', 0)
        ->assertJsonPath('data.teams.1.score', 0);

    // Verify history record created
    expect(GameSessionHistory::where('game_session_id', $gameId)->count())->toBe(1);

    $history = GameSessionHistory::where('game_session_id', $gameId)->first();
    expect($history->final_scores)->toHaveKey('Team 1')
        ->and($history->final_scores['Team 1'])->toBe(200);
});
