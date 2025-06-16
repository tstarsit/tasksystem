<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Audit extends Model
{
    use HasFactory;


    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    const array Type=[
        '1'=>'Edit',
        '2'=>'Assign',
        '3'=>'Delete',
        '4'=>'Restore'
    ];
}
