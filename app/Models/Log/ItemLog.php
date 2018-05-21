<?php

namespace App\Models\Log;

use Illuminate\Database\Eloquent\Model;

class ItemLog extends Model
{
    protected $connection = 'mysql-log';
    protected $table = 'item_log';
    public $timestamps = false;
}
