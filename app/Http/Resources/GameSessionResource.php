<?php

namespace App\Http\Resources;

use App\Models\GameSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin GameSession
 */
class GameSessionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $remainingSeconds = $this->getRemainingTimerSeconds();

        return [
            'id' => $this->id,
            'session_code' => $this->session_code,
            'status' => $this->status->value,
            'is_started' => $this->status->value !== 'not_started',
            'is_completed' => $this->status->value === 'completed',
            'current_section' => $this->currentSection ? [
                'id' => $this->currentSection->id,
                'title' => $this->currentSection->title,
                'order' => $this->currentSection->order,
            ] : null,
            'current_question' => $this->currentQuestion ? [
                'id' => $this->currentQuestion->id,
                'question_text' => $this->currentQuestion->question_text,
                'points' => $this->currentQuestion->points,
                'order' => $this->currentQuestion->order,
            ] : null,
            'current_team' => $this->currentTeam ? [
                'id' => $this->currentTeam->id,
                'name' => $this->currentTeam->name,
                'color_code' => $this->currentTeam->color_code,
            ] : null,
            'current_question_number' => $this->current_question_number,
            'total_questions_answered' => $this->total_questions_answered,
            'timer' => [
                'duration_seconds' => $this->timer_duration_seconds,
                'remaining_seconds' => $remainingSeconds,
                'is_expired' => $this->status->value === 'in_progress' && $this->current_question_expires_at && $remainingSeconds === 0,
                'started_at' => $this->current_question_started_at?->toISOString(),
                'expires_at' => $this->current_question_expires_at?->toISOString(),
            ],
            'teams' => TeamResource::collection($this->teams),
            'started_at' => $this->started_at?->toISOString(),
            'ended_at' => $this->ended_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
