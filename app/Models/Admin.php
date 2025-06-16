<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\HasDatabaseNotifications;
use Illuminate\Notifications\Notifiable;

class Admin extends Model
{
    use HasFactory,Notifiable,HasDatabaseNotifications;

protected $primaryKey='user_id';


    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function scopeActive(Builder $query): void
    {
        $query->whereHas('user', function ($query) {
            $query->where('status', 1); // Filter users with status = 1
        });
    }
    public function ticket()
    {
        return $this->hasMany(Ticket::class,'solved_by','user_id');
    }

}
