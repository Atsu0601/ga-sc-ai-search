<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, Billable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'trial_ends_at' => 'datetime',
        ];
    }

    public function company()
    {
        return $this->hasOne(Company::class);
    }

    public function websites(): HasMany
    {
        return $this->hasMany(Website::class);
    }

    /**
     * ユーザーの現在のサブスクリプションプランを取得
     */
    public function getCurrentPlan()
    {
        $plan = Plan::where('name', $this->plan_name)->first();

        if (!$plan && $this->subscription('default')) {
            // Stripeサブスクリプションが存在する場合、そこからプラン情報を取得
            $stripePlanId = $this->subscription('default')->stripe_plan;
            $plan = Plan::where('stripe_plan_id', $stripePlanId)->first();
        }

        return $plan;
    }

    /**
     * ユーザーがサブスクリプションを持っているかどうかを確認
     */
    public function hasActiveSubscription()
    {
        return $this->subscription_status !== 'trial' || $this->subscription('default')->active();
    }
}
