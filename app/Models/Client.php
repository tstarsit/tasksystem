<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;
    protected $primaryKey='user_id';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ticket()
    {
        return $this->hasMany(Ticket::class,'client_id','user_id');
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('status', 1);
    }

    public function smsclient()
    {
        return $this->belongsTo(SmsClient::class, 'client_id', 'user_id');
    }

}
