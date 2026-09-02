<?php

namespace App\Models;

use App\Enums\GameStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class GameSession extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'session_code',
        'status',
        'current_section_id',
        'current_question_id',
        'current_team_id',
        'current_question_number',
        'total_questions_answered',
        'started_at',
        'ended_at',
        'current_question_started_at',
        'current_question_expires_at',
        'timer_duration_seconds',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => GameStatus::class,
            'current_question_number' => 'integer',
            'total_questions_answered' => 'integer',
            'timer_duration_seconds' => 'integer',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'current_question_started_at' => 'datetime',
            'current_question_expires_at' => 'datetime',
        ];
    }

    public function currentSection(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'current_section_id');
    }

    public function currentQuestion(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'current_question_id');
    }

    public function currentTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'current_team_id');
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'game_session_teams')
            ->withPivot(['team_order', 'score'])
            ->withTimestamps()
            ->orderBy('game_session_teams.team_order');
    }

    public function sessionTeams(): HasMany
    {
        return $this->hasMany(GameSessionTeam::class)->orderBy('team_order');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(GameSessionHistory::class)->latest();
    }

    public function scoreLogs(): HasMany
    {
        return $this->hasMany(ScoreLog::class)->latest('id');
    }

    /**
     * Calculate the remaining seconds on the current question timer.
     */
    public function getRemainingTimerSeconds(): int
    {
        if (! $this->current_question_expires_at || $this->status !== GameStatus::InProgress) {
            return 0;
        }

        $now = Carbon::now();
        if ($now->greaterThanOrEqualTo($this->current_question_expires_at)) {
            return 0;
        }

        return (int) $now->diffInSeconds($this->current_question_expires_at, false);
    }
}
