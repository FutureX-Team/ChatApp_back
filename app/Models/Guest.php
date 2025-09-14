<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Auth\Authenticatable;

class Guest extends Model implements AuthenticatableContract
{
    use HasApiTokens, Authenticatable;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'nickname','device_id','user_agent_hash','ip_hash','is_blocked',
    ];

    protected static function booted()
    {
        static::creating(function (Guest $guest) {
            if (empty($guest->id)) $guest->id = (string) Str::uuid();
            if (empty($guest->nickname)) $guest->nickname = 'guest_'.uniqid();
        });

        static::created(function (Guest $guest) {
            $target = 'guest_'.$guest->id;
            if ($guest->nickname !== $target) {
                $guest->nickname = $target;
                $guest->saveQuietly();
            }
        });
    }

    public function tweets()
    {
        return $this->hasMany(Tweet::class);
    }
}
