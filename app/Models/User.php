<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use Notifiable, HasApiTokens;

    protected $table = 'users';

    // ما عندك updated_at، فقط created_at من الداتا بيس
    public $timestamps = false;

    // UUID
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'username',
        'email',
        'password_hash',
        'avatar_url',
        'google_id',
        'is_disabled',
        'dark_mode',
        'created_at', // لو عندك DEFAULT CURRENT_TIMESTAMP عادي تبقيها
    ];

    protected $hidden = [
        'password_hash',
        'remember_token', // وجوده هنا ما يضر حتى لو العمود غير موجود
    ];

    protected $casts = [
        'is_disabled' => 'boolean',
        'dark_mode'   => 'boolean',
        'created_at'  => 'datetime',
    ];

    // Laravel يستخدم password_hash بدلاً من password
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->getKey()) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
            // لو تبغى تضمن created_at عند الإنشاء (بما إن timestamps=false)
            if (!$model->created_at) {
                $model->created_at = now();
            }
        });
    }
}
