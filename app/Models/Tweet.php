<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tweet extends Model
{
    use HasFactory;
    protected $keyType = 'string';
    public $incrementing = false;
    // نوقف updated_at فقط، ونترك created_at يعمل تلقائياً
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'guest_id',
        'text',
        'place_id',
        'reply_to_tweet_id',
        'up_count',
        'down_count',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function guest()
    {
        return $this->belongsTo(Guest::class, 'guest_id');
    }


    public function replies()
    {
        return $this->hasMany(Tweet::class, 'reply_to_tweet_id');
    }

    public function parent()
    {
        return $this->belongsTo(Tweet::class, 'reply_to_tweet_id');
    }

    public function place()
    {
        return $this->belongsTo(Place::class);
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }
}
