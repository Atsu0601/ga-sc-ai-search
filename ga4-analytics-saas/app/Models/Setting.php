<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    /**
     * 複数代入可能な属性
     *
     * @var array
     */
    protected $fillable = [
        'key',
        'value',
        'group',
        'description',
    ];

    /**
     * 設定を取得
     */
    public static function get(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();

        if (!$setting) {
            return $default;
        }

        return $setting->value;
    }

    /**
     * 設定を保存
     */
    public static function set(string $key, $value, string $group = 'general', string $description = null)
    {
        $setting = self::firstOrNew(['key' => $key]);
        $setting->value = $value;
        $setting->group = $group;

        if ($description && !$setting->exists) {
            $setting->description = $description;
        }

        $setting->save();

        return $setting;
    }

    /**
     * 設定グループを取得
     */
    public static function getGroup(string $group)
    {
        return self::where('group', $group)->get()->keyBy('key');
    }
}
