<?php

namespace App\Providers;

use App\Services\Platform\EncryptionService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use App\Models\Platform\Server;

class DynamicGameDbServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(Request $request)
    {
        if (! $request->has('db')) {
            return true;    //使用游戏数据库在配置文件中的默认连接
        }

        $dbServer = Server::findOrFail($request->db);

        $dbPass = EncryptionService::publicDecrypt(base64_decode($dbServer->mysql_passwd));
        Config::set('database.connections.mysql-game', [	//更改配置项
            'driver' => 'mysql',
            'host' => $dbServer->mysql_host,
            'port' => $dbServer->mysql_port,
            'database' => $dbServer->mysql_data_name,   //qipai_data数据库
            'username' => $dbServer->mysql_user,
            'password' => $dbPass,
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ]);

        Config::set('database.connections.mysql-log', [	//更改配置项
            'driver' => 'mysql',
            'host' => $dbServer->mysql_host,
            'port' => $dbServer->mysql_port,
            'database' => $dbServer->mysql_log_name,    //qipai_log数据库
            'username' => $dbServer->mysql_user,
            'password' => $dbPass,
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
