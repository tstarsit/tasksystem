<?php

namespace App\Models;

use App\Enums\TicketStatus;
use App\Filament\Resources\TicketResource\Pages\EditTicket;
use App\Observers\TicketObserver;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Filament\Notifications\Actions\Action;
use Illuminate\Support\Carbon;
#[ObservedBy([TicketObserver::class])]

class Ticket extends Model
{
    use HasFactory,SoftDeletes;
    protected $casts = [
        'accepted_date' => 'datetime',
        'delivered_date' => 'datetime',
    ];


    const  SYSTEM = [
        '1' => 'NAS',
        '2' => 'DINAR',
        '3' => 'BEE',
//        '4' => 'SALAM',
    ];


    const  STATUS = [
        1 => 'Resolved',
        2 => 'Pending',
        3 => 'In Progress',
        4 => 'Paid',
    ];
    const  SERVICES = [
        1 => 'Maintenance',
        2 => 'Request',
        3 => 'Development',

    ];

    public function getStatusNameAttribute()
    {
        return self::STATUS[$this->status] ?? 'Unknown';
    }



    public function getSystemNameAttribute()
    {
        return self::SYSTEM[$this->system_id] ?? 'Unknown';
    }

    public function scopeActive()
    {
        return $this->where('isCanceled',0);
    }

    public function solver()
    {
        return $this->belongsTo(Admin::class, 'solved_by','user_id');
    }
    public function accepted()
    {
        return $this->belongsTo(Admin::class, 'accepted_by','user_id');
    }
    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id', 'user_id');
    }
    public function admin()
    {
        return $this->belongsTo(Admin::class, 'solved_by','user_id');
    }

    public function assigned()
    {
        return $this->belongsTo(Admin::class, 'assigned_to','user_id');
    }
}
