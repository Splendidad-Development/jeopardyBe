<?php

namespace App\Http\Resources;

use App\Models\GameSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin GameSession
 */
class CurrentQuestionResource extends JsonResource
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
            'game_id' => $this->id,
            'session_code' => $this->session_code,
            'status' => $this->status->value,
            'section' => $this->currentSection ? [
                'id' => $this->currentSection->id,
                'title' => $this->currentSection->title,
                'order' => $this->currentSection->order,
            ] : null,
            'question' => $this->currentQuestion ? [
                'id' => $this->currentQuestion->id,
                'question_text' => $this->currentQuestion->question_text,
                'points' => $this->currentQuestion->points,
                'order' => $this->currentQuestion->order,
                'number_in_section' => $this->current_question_number,
                'total_answered' => $this->total_questions_answered,
            ] : null,
            'assigned_team' => $this->currentTeam ? [
                'id' => $this->currentTeam->id,
                'name' => $this->currentTeam->name,
                'color_code' => $this->currentTeam->color_code,
            ] : null,
            'timer' => [
                'duration_seconds' => $this->timer_duration_seconds,
                'remaining_seconds' => $remainingSeconds,
                'is_expired' => $this->status->value === 'in_progress' && $this->current_question_expires_at && $remainingSeconds === 0,
                'started_at' => $this->current_question_started_at?->toISOString(),
                'expires_at' => $this->current_question_expires_at?->toISOString(),
            ],
        ];
    }
}
