<?php

namespace App\Models\Log;

use Illuminate\Database\Eloquent\Model;

class RoleLogin extends Model
{
    protected $connection = 'mysql-log';
    protected $table = 'role_login';
    public $timestamps = false;
}
