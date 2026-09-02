<?php

use App\Models\Question;
use App\Models\Section;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->section = Section::create(['title' => 'Section 1', 'order' => 1, 'is_active' => true]);
    $this->question = Question::create(['section_id' => $this->section->id, 'question_text' => 'Q1', 'points' => 100, 'order' => 1]);
    $this->moderatorKey = config('game.moderator_secret', 'moderator-secret-key-12345');
});

test('leaderboard shows distinct ranks and winner when scores differ', function () {
    $gameResponse = $this->postJson('/api/games');
    $gameId = $gameResponse->json('data.id');
    $team1Id = $gameResponse->json('data.teams.0.id');
    $team2Id = $gameResponse->json('data.teams.1.id');

    // Set Team 1 = 500, Team 2 = 300
    $this->withHeader('X-Moderator-Key', $this->moderatorKey)
        ->putJson("/api/games/{$gameId}/scores", [
            'team1_score' => 500,
            'team2_score' => 300,
        ]);

    $response = $this->getJson("/api/games/{$gameId}/leaderboard");

    $response->assertOk()
        ->assertJsonPath('data.standings.0.team_id', $team1Id)
        ->assertJsonPath('data.standings.0.rank', 1)
        ->assertJsonPath('data.standings.0.score', 500)
        ->assertJsonPath('data.standings.0.is_winner', true)
        ->assertJsonPath('data.standings.0.is_tie', false)
        ->assertJsonPath('data.standings.1.team_id', $team2Id)
        ->assertJsonPath('data.standings.1.rank', 2)
        ->assertJsonPath('data.standings.1.score', 300)
        ->assertJsonPath('data.standings.1.is_winner', false);
});

test('leaderboard correctly handles ties', function () {
    $gameResponse = $this->postJson('/api/games');
    $gameId = $gameResponse->json('data.id');

    // Set both teams = 400
    $this->withHeader('X-Moderator-Key', $this->moderatorKey)
        ->putJson("/api/games/{$gameId}/scores", [
            'team1_score' => 400,
            'team2_score' => 400,
        ]);

    $response = $this->getJson("/api/games/{$gameId}/leaderboard");

    $response->assertOk()
        ->assertJsonPath('data.standings.0.rank', 1)
        ->assertJsonPath('data.standings.0.is_tie', true)
        ->assertJsonPath('data.standings.0.is_winner', false)
        ->assertJsonPath('data.standings.1.rank', 1)
        ->assertJsonPath('data.standings.1.is_tie', true)
        ->assertJsonPath('data.standings.1.is_winner', false);
});

test('global leaderboard is accessible', function () {
    $response = $this->getJson('/api/leaderboard');

    $response->assertOk()
        ->assertJsonStructure(['data']);
});
