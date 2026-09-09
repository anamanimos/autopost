<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TokenActivityLog extends Model
{
    protected $fillable = [
        'action',
        'status',
        'details',
    ];
}
