<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Observers\UserObserver;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Notifications\Notification;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
class User extends Authenticatable implements HasName,FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable,HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected static function boot()
    {
        parent::boot();

        static::updating(function ($user) {
            $original = $user->getOriginal();


            // Update accepted_date and delivered_date based on changes
            if ($user->isDirty('system_id')) {

//                $user->system_id =$original
                $user->status=3;
            }



        });
    }
    public function audits(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Audit::class);
    }

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function admin()
    {
        return $this->hasOne(Admin::class);
    }
    public function scopeActive(Builder $query): void
    {
        $query->where('status', 1);
    }
    public function client()
    {
        return $this->hasOne(Client::class);
    }


    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function getFilamentName(): string
    {

       return  $this->type==1?$this->username:$this->client->name;
    }

    public function getNameAttribute()
    {
        try {
            // Ensure the admin relationship is loaded before accessing it
            $this->loadMissing('admin', 'client');

            return $this->type == 1 ? ($this->admin?->name ?? 'No Admin') : ($this->client?->name ?? 'No Client');
        } catch (\Exception $e) {
            dd($this);
        }


    }
    public function solvedTickets()
    {
        return $this->hasMany(Ticket::class, 'solved_by');
    }


    public function canAccessPanel(Panel $panel): bool
    {
       return true;
    }
}
