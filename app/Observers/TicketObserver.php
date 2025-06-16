<?php

namespace App\Observers;

use App\Events\TicketNotification;
use App\Filament\Resources\TicketResource;
use App\Models\Admin;
use App\Models\Client;
use Filament\Notifications\Actions\Action;

use App\Models\Ticket;
use App\Models\User;
use Filament\Notifications\Events\DatabaseNotificationsSent;
use Filament\Notifications\Notification;

class TicketObserver
{
    /**
     * Handle the Ticket "created" event.
     */
    public function created(Ticket $ticket): void
    {


//        $user = User::role('Head')->with('admin')->whereHas('admin',function ($query) use($ticket){
//            $query->where('system_id',$ticket->system_id);
//        })->first(); // Returns only users with the role 'writer'
//
//
//            Notification::make()
//                ->title('A Ticket has been assigned to you')
//                ->sendToDatabase($user);
//
//                event(new DatabaseNotificationsSent($user));

//        $user->notify(
//            Notification::make()
//                ->title(__('New Ticket'))
//                ->body("{$ticket->client?->name}")
//                ->icon('icon-ticket')
//                ->actions([
//                    Action::make('View')
//                        ->button()
//                        ->url(TicketResource::getUrl('edit', ['record' => $ticket])),
//                ])
//                ->toDatabase()
//            ,
//
//        );

    }

    /**
     * Handle the Ticket "updated" event.
     */
    public function updated(Ticket $ticket): void
    {

    }

    /**
     * Handle the Ticket "deleted" event.
     */
    public function deleted(Ticket $ticket): void
    {
        //
    }

    /**
     * Handle the Ticket "restored" event.
     */
    public function restored(Ticket $ticket): void
    {
        //
    }

    /**
     * Handle the Ticket "force deleted" event.
     */
    public function forceDeleted(Ticket $ticket): void
    {
        //
    }
}
