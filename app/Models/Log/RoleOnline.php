<?php

namespace App\Models\Log;

use Illuminate\Database\Eloquent\Model;

class RoleOnline extends Model
{
    protected $connection = 'mysql-log';
    protected $table = 'role_online';
    public $timestamps = false;
}
