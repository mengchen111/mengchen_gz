<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TopUpAdmin extends Model
{
    protected $table = 'top_up_admin';
    protected $primaryKey = 'id';

    protected $fillable = [
        'provider', 'receiver', 'type', 'amount'
    ];



}