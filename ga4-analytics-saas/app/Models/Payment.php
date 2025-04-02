<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    /**
     * 複数代入可能な属性
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'stripe_payment_id',
        'plan_name',
        'amount',
        'currency',
        'payment_method',
        'status',
        'payment_date',
        'next_payment_date',
        'receipt_url',
    ];

    /**
     * 日付として扱う属性
     *
     * @var array
     */
    protected $casts = [
        'amount' => 'float',
        'payment_date' => 'datetime',
        'next_payment_date' => 'datetime',
    ];

    /**
     * ユーザーとのリレーション
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
