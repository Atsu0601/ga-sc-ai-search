<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    /**
     * 複数代入可能な属性
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'price',
        'billing_period',
        'stripe_plan_id',
        'website_limit',
        'is_active',
        'is_featured',
    ];

    /**
     * 真偽値へキャストする属性
     *
     * @var array
     */
    protected $casts = [
        'price' => 'float',
        'website_limit' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    /**
     * ユーザーとのリレーション
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'plan_name', 'name');
    }

    /**
     * アクティブなプランを取得
     */
    public static function getActive()
    {
        return self::where('is_active', true)->orderBy('price')->get();
    }
}
