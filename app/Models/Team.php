<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'color_code',
    ];

    /**
     * The game sessions the team is in.
     */
    public function gameSessions(): BelongsToMany
    {
        return $this->belongsToMany(GameSession::class, 'game_session_teams')
            ->withPivot(['team_order', 'score'])
            ->withTimestamps();
    }

    /**
     * The pivot records for the team in games.
     */
    public function gameSessionTeams(): HasMany
    {
        return $this->hasMany(GameSessionTeam::class);
    }
}
