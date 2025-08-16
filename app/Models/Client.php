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
// In Client model
    public function scopeForCurrentUser($query)
    {

        $userId = auth()->id();

        // Special case - only show client ID 141 for users 119 or 110
        if ($userId == 119 || $userId == 110) {
            return $query->where('user_id', 141);
        }

        // For users with 'web' role - only show clients with name 'web'
        if (auth()->user()->hasRole('Web')) {
            return $query->where('name', 'web');
        }

        // Default case - no clients shown (or adjust as needed)
        return $query->whereRaw('1=0'); // Returns no results
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
