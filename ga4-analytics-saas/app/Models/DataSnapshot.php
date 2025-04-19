<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DataSnapshot extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * 複数代入可能な属性
     *
     * @var array
     */
    protected $fillable = [
        'website_id',
        'snapshot_date',
        'snapshot_type',
        'data_json',
    ];

    /**
     * JSONとして扱う属性
     *
     * @var array
     */
    protected $casts = [
        'snapshot_date' => 'date',
        'data_json' => 'array',
    ];

    /**
     * ウェブサイトとのリレーション
     */
    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }
}
