<?php

namespace App\Http\Resources;

use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Team
 */
class TeamResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'color_code' => $this->color_code,
            'order' => $this->whenPivotLoaded('game_session_teams', fn () => $this->pivot->team_order),
            'score' => $this->whenPivotLoaded('game_session_teams', fn () => $this->pivot->score),
        ];
    }
}
