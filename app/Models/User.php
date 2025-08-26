<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use Notifiable;
    use HasApiTokens;

    protected $table = 'users';

    // جدولك لا يحتوي updated_at (فقط created_at بـ DEFAULT CURRENT_TIMESTAMP)
    public $timestamps = false;

    protected $fillable = [
        'username',
        'email',
        'password_hash',
        'avatar_url',
        'is_disabled',
        'dark_mode',
    ];

    protected $hidden = [
        'password_hash',
        // ما عندك remember_token بالجدول، تركه هنا لا يضر لأنه غير موجود فعليًا
        'remember_token',
    ];

    protected $casts = [
        'is_disabled' => 'boolean',
        'dark_mode'   => 'boolean',
        'created_at'  => 'datetime',
    ];

    // مهم: خلي Laravel يستخدم عمود password_hash عند التحقق من كلمة المرور
    public function getAuthPassword()
    {
        return $this->password_hash;
    }
}
