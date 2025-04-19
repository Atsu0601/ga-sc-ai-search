<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'website_id',
        'property_id',
        'view_id',
        'access_token',
        'refresh_token',
        'last_synced_at',
    ];

    protected $casts = [
        // 'access_token' => 'encrypted',
        // 'refresh_token' => 'encrypted',
        'last_synced_at' => 'datetime',
    ];

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }
}
