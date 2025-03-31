<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Website extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'url',
        'description',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function analyticsAccount(): HasOne
    {
        return $this->hasOne(AnalyticsAccount::class);
    }

    public function searchConsoleAccount(): HasOne
    {
        return $this->hasOne(SearchConsoleAccount::class);
    }

    public function dataSnapshots()
    {
        return $this->hasMany(DataSnapshot::class);
    }
}
