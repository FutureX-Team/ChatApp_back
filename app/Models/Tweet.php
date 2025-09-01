<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tweet extends Model
{
    use HasFactory;
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'text',
        'place_id',
        'reply_to_tweet_id',
        'up_count',
        'down_count',
    ];

    // صاحب التغريدة
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // الردود على التغريدة
    public function replies()
    {
        return $this->hasMany(Tweet::class, 'reply_to_tweet_id');
    }

    // لو التغريدة نفسها رد على تغريدة ثانية
    public function parent()
    {
        return $this->belongsTo(Tweet::class, 'reply_to_tweet_id');
    }

    // المكان المرتبط بالتغريدة (اختياري)
    public function place()
    {
        return $this->belongsTo(Place::class);
    }

    // البلاغات المرتبطة بالتغريدة
    public function reports()
    {
        return $this->hasMany(Report::class);
    }
}
