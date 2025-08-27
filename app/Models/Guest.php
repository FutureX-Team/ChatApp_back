<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class Guest extends Model
{
    use HasApiTokens;

    protected $fillable = [
        'nickname',
        'device_id',
        'user_agent_hash',
        'ip_hash',
        'is_blocked',
    ];

    // علاقات
    public function tweets()
    {
        return $this->hasMany(Tweet::class);
    }

    // توليد اسم تلقائي
    protected static function booted()
    {
        // قبل الإنشاء: اسم مؤقت
        static::creating(function (Guest $guest) {
            if (empty($guest->nickname)) {
                $guest->nickname = 'guest_' . uniqid();
            }
        });

        // بعد الإنشاء: ثبّت الاسم ليكون guest_{id}
        static::created(function (Guest $guest) {
            $target = 'guest_' . $guest->id;
            if ($guest->nickname !== $target) {
                $guest->nickname = $target;
                $guest->saveQuietly();
            }
        });
    }
}
