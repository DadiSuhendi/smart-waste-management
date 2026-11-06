<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'reference' => $this->reference(), 'title' => $this->title,
            'description' => $this->description, 'category' => $this->category->value,
            'category_label' => $this->category->label(), 'status' => $this->status->value,
            'status_label' => $this->status->label(), 'address' => $this->address,
            'latitude' => $this->latitude, 'longitude' => $this->longitude,
            'photo_url' => route('api.reports.photo', $this->id),
            'ai_category' => $this->ai_category, 'ai_confidence' => $this->ai_confidence,
            'created_at' => $this->created_at->toIso8601String(), 'updated_at' => $this->updated_at->toIso8601String(),
            'user' => $this->whenLoaded('user', fn () => ['id' => $this->user->id, 'name' => $this->user->name]),
            'histories' => $this->whenLoaded('histories', fn () => $this->histories->map(fn ($history) => [
                'id' => $history->id, 'status' => $history->status->value, 'status_label' => $history->status->label(),
                'note' => $history->note, 'actor' => $history->user?->name ?? 'Pengguna dihapus',
                'created_at' => $history->created_at->toIso8601String(),
            ])),
        ];
    }
}
