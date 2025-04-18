<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Website extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'url',
        'ga4_property_id',
        'ga4_credentials',
        'search_console_site_url',
        'user_id',
        'status',
        'description'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'ga4_credentials' => 'encrypted',
    ];

    /**
     * Get the user that owns the website.
     */
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
