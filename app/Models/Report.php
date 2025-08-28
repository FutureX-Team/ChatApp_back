<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = ['tweet_id', 'user_id', 'reason', 'status'];

    public function tweet()
    {
        return $this->belongsTo(Tweet::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
