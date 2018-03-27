<?php

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Model;

class Platform extends Model
{
    protected $connection = 'mysql-platform';
    protected $table = 'platform';
    public $timestamps = false;
    protected $visible = [
        'flag', 'name'
    ];
}
