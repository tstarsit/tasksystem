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

    protected static function boot()
    {
        parent::boot();


        static::updating(function ($ticket) {
            $original = $ticket->getOriginal();

            foreach ($ticket->getDirty() as $attribute => $newValue) {
                if ($original[$attribute] != $newValue) {
                    Audit::create([
                        'ticket_id' => $ticket->id,
                        'user_id' => auth()->id(),
                        'changed_column' => $attribute,
                        'old_value' => $original[$attribute] ?? null,
                        'new_value' => $newValue,
                        'change_type' => 2, // You can adjust this as needed
                    ]);
                }
            }

            // Update accepted_date and delivered_date based on changes
            if ($ticket->isDirty('service_id')) {
                $ticket->accepted_date = now();
                $ticket->status=3;
            }
            if ($ticket->isDirty('solution')) {
                $user=User::find($ticket->client_id);
                $user->notify(
                    Notification::make()
                        ->title('تم حل المشكلة')
                        ->icon('heroicon-o-document-text')
                        ->actions([
                            Action::make('view')
                                ->label('View Ticket')
                                ->icon('heroicon-o-eye')
                                ->url(EditTicket::getUrl(['record' => $ticket->id]))
                                ->openUrlInNewTab(), // optional
                        ])
                        ->toDatabase()
                    ,
                );

                $ticket->delivered_date = now();
                $ticket->solved_by = auth()->user()->id;
                $ticket->status=1;
            }

            if ($ticket->isDirty('assigned_to')) {
                $user=User::find($ticket->assigned_to);
                $user->notify(
                    Notification::make()
                        ->title('A Ticket has been assigned to you')
                        ->icon('heroicon-o-document-text')
                        ->actions([
                            Action::make('view')
                                ->label('View Ticket')
                                ->icon('heroicon-o-eye')
                                ->url(EditTicket::getUrl(['record' => $ticket->id]))
                                ->openUrlInNewTab(), // optional
                        ])
                        ->toDatabase()
                    ,
                );
            }
        });
    }

    const  SYSTEM = [
        '1' => 'NAS',
        '2' => 'DINAR',
        '3' => 'BEE',
        '4' => 'SALAM',
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
    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id', 'user_id');
    }
    public function admin()
    {
        return $this->belongsTo(Admin::class, 'solved_by','user_id');
    }
}
