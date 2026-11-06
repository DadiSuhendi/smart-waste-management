<?php

namespace App\Models;

use App\Enums\ReportStatus;
use App\Enums\WasteCategory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'description', 'category', 'address', 'latitude', 'longitude', 'photo_path'])]
class WasteReport extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['category' => WasteCategory::class, 'status' => ReportStatus::class, 'latitude' => 'float', 'longitude' => 'float', 'ai_confidence' => 'float'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(StatusHistory::class)->orderByDesc('id');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->when(! $user->isAdmin(), fn (Builder $q) => $q->where('user_id', $user->id));
    }

    public function reference(): string
    {
        return 'SW-'.str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }
}
