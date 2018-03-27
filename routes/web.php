<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', 'HomeController@index');

// Authentication Routes...
Route::get('login', 'Auth\LoginController@showLoginForm')->name('login');
Route::post('login', 'Auth\LoginController@login');
Route::post('logout', 'Auth\LoginController@logout')->name('logout');

//开发调试功能接口
Route::prefix('dev')->group(function () {
    Route::get('list-session', 'DevToolsController@listSession');
    Route::get('hashed-pass/{pass}', 'DevToolsController@hashedPass');
    Route::post('base64-decode', 'DevToolsController@base64Decode');
});

Route::prefix('api')->group(function () {
    Route::get('info', 'InfoController@info');  //网站的管理员和代理商后台的公共接口
    Route::get('content-header-h1', 'InfoController@getContentHeaderH1');

    //微信支付相关
    Route::post('wechat/order', 'WeChatPaymentController@createOrder');
    Route::delete('wechat/order/{order}', 'WeChatPaymentController@closeOrder');
    Route::get('wechat/order/{orderId?}', 'WeChatPaymentController@getOrder');
    Route::get('wechat/order/status/{outTradeNo}', 'WeChatPaymentController@checkOrderStatus');
    Route::any('wechat/order/notification', 'WeChatPaymentController@getNotification');
    Route::get('order/item', 'WeChatPaymentController@getItemPrice');

    //platform接口
    Route::get('server/lists', 'Platform\ServerListController@show');
    Route::get('api/func_switch_version', 'Platform\VersionController@showFuncSwitchVersion');
    Route::post('api/client_feedback', 'Platform\ClientController@collectClientFeedback');
    Route::post('api/client_error_log', 'Platform\ClientController@collectClientErrorLog');
    Route::get('api/cnotice', 'Platform\NoticeController@showLoginNotice');
    Route::post('headimg', 'Platform\HeadimgController@upload');
});
//管理员接口
Route::group([
    'middleware' => ['auth'],
    'prefix' => 'admin/api',
    'namespace' => 'Admin'
], function () {
    Route::put('self/password', 'AdminController@updatePass');

    Route::get('home/summary', 'HomeController@summaryReport');

    Route::get('statement/hourly', 'StatementController@hourly');
    Route::get('statement/daily', 'StatementController@daily');
    Route::get('statement/monthly', 'StatementController@monthly');
    Route::get('statement/hourly-chart', 'StatementController@hourlyChart');

    Route::get('game/player', 'Game\PlayerController@show');

    Route::get('game/notification/marquee', 'Game\MarqueeNotificationController@show');
    Route::post('game/notification/marquee', 'Game\MarqueeNotificationController@create');
    Route::put('game/notification/marquee/{marquee}', 'Game\MarqueeNotificationController@update')->where('marquee', '[0-9]+');
    Route::delete('game/notification/marquee/{marquee}', 'Game\MarqueeNotificationController@destroy')->where('marquee', '[0-9]+');
    Route::put('game/notification/marquee/enable/{marquee}', 'Game\MarqueeNotificationController@enable')->where('marquee', '[0-9]+');
    Route::put('game/notification/marquee/disable/{marquee}', 'Game\MarqueeNotificationController@disable')->where('marquee', '[0-9]+');
    Route::get('game/notification/login', 'Game\LoginNotificationController@show');
    Route::post('game/notification/login', 'Game\LoginNotificationController@create');
    Route::put('game/notification/login/{notification}', 'Game\LoginNotificationController@update')->where('notification', '[0-9]+');
    Route::delete('game/notification/login/{notification}', 'Game\LoginNotificationController@destroy')->where('notification', '[0-9]+');
    Route::put('game/notification/login/enable/{notification}', 'Game\LoginNotificationController@enable')->where('notification', '[0-9]+');
    Route::put('game/notification/login/disable/{notification}', 'Game\LoginNotificationController@disable')->where('notification', '[0-9]+');

    Route::get('game/room/friend', 'Game\FriendRoomController@show');
    Route::delete('game/room/friend/{ownerId}', 'Game\FriendRoomController@dismiss')->where('ownerId', '[0-9]+');
    Route::get('game/room/coin', 'Game\CoinRoomController@show');
    Route::delete('game/room/coin/{roomId}', 'Game\CoinRoomController@dismiss')->where('roomId', '[0-9]+');

    Route::get('game/ai/list', 'Game\AiController@show');
    Route::get('game/ai/dispatch/list', 'Game\AiController@showDispatch');
    Route::get('game/ai/type-map', 'Game\AiController@getMaps');
    Route::put('game/ai', 'Game\AiController@edit');
    Route::post('game/ai', 'Game\AiController@addSingleAi');
    Route::post('game/ai/mass', 'Game\AiController@addMassAi');
    Route::post('game/ai/quick', 'Game\AiController@quickAddAi');
    Route::put('game/ai/mass', 'Game\AiController@massEdit');
    Route::put('game/ai-dispatch', 'Game\AiController@editDispatch');
    Route::post('game/ai-dispatch', 'Game\AiController@addDispatch');
    Route::put('game/ai-dispatch/switch/{id}/{switch}', 'Game\AiController@switchAiDispatch')->where('id', '[0-9]+')->where('switch', '[01]');

    Route::get('game/whitelist', 'Game\WhitelistController@listWhitelist');
    Route::post('game/whitelist', 'Game\WhitelistController@addWhitelist');
    Route::put('game/whitelist', 'Game\WhitelistController@editWhiteList');
    Route::delete('game/whitelist', 'Game\WhitelistController@deleteWhitelist');

    Route::get('platform/server', 'Platform\ServerController@show');
    Route::get('platform/server/list', 'Platform\ServerController@serverList');
    Route::get('platform/server/map', 'Platform\ServerController@getServerDataMap');
    Route::put('platform/server/{server}', 'Platform\ServerController@editServer')->where('server', '[0-9]+');
    Route::post('platform/server', 'Platform\ServerController@createServer');
    Route::delete('platform/server/{server}', 'Platform\ServerController@deleteServer')->where('server', '[0-9]+');

    Route::post('stock', 'StockController@apply');
    Route::get('stock/list', 'StockController@applyList');
    Route::get('stock/history', 'StockController@applyHistory');
    Route::post('stock/approval/{entry}', 'StockController@approve')->where('entry', '[0-9]+');
    Route::post('stock/decline/{entry}', 'StockController@decline')->where('entry', '[0-9]+');

    Route::get('agent', 'AgentController@showAll');
    Route::post('agent', 'AgentController@create');
    Route::delete('agent/{user}', 'AgentController@destroy')->where('user', '[0-9]+');
    Route::put('agent/{user}', 'AgentController@update')->where('user', '[0-9]+');
    Route::put('agent/pass/{user}', 'AgentController@updatePass')->where('user', '[0-9]+');

    Route::get('top-up/admin', 'TopUpController@admin2AgentHistory');
    Route::get('top-up/agent', 'TopUpController@agent2AgentHistory');
    Route::get('top-up/player', 'TopUpController@agent2PlayerHistory');
    Route::post('top-up/agent/{receiver}/{type}/{amount}', 'TopUpController@topUp2Agent')->where('amount', '[0-9]+');
    Route::post('top-up/player/{player}/{type}/{amount}', 'TopUpController@topUp2Player')->where('amount', '-?[0-9]+');

    Route::get('order/item', 'ItemController@show');
    Route::put('order/item/{item}', 'ItemController@editPrice')->where('item', '[0-9]+');

    Route::get('system/log', 'SystemController@showLog');

    //功能开关控制
    Route::get('platform/func-switch','Platform\FuncSwitchController@index');
    Route::get('platform/func-switch/form-info/{id?}','Platform\FuncSwitchController@formInfo');
    Route::post('platform/func-switch','Platform\FuncSwitchController@store');
    Route::put('platform/func-switch/{func}','Platform\FuncSwitchController@update')->where('func', '[0-9]+');
    Route::delete('platform/func-switch/{func}','Platform\FuncSwitchController@destroy')->where('func','[0-9]+');
});

//管理员视图路由
Route::group([
    'middleware' => ['auth'],
    'prefix' => 'admin',
    'namespace' => 'Admin'
], function () {
    Route::get('home', 'ViewController@home');

    Route::get('statement/hourly', 'ViewController@statementHourly');
    Route::get('statement/daily', 'ViewController@statementDaily');
    Route::get('statement/monthly', 'ViewController@statementMonthly');

    Route::get('player/list', 'ViewController@playerList');

    Route::get('gm/notification/marquee', 'ViewController@gmNotificationMarquee');
    Route::get('gm/notification/login', 'ViewController@gmNotificationLogin');
    Route::get('gm/room/friend', 'ViewController@gmRoomFriend');
    Route::get('gm/room/coin', 'ViewController@gmRoomCoin');
    Route::get('gm/ai/list', 'ViewController@gmAiList');
    Route::get('gm/whitelist/list', 'ViewController@gmWhitelistList');
    Route::get('gm/server/list', 'ViewController@gmServerList');

    Route::get('stock/apply-request', 'ViewController@stockApplyRequest');
    Route::get('stock/apply-list', 'ViewController@stockApplyList');
    Route::get('stock/apply-history', 'ViewController@stockApplyHistory');

    Route::get('agent/list', 'ViewController@agentList');
    Route::get('agent/create', 'ViewController@agentCreate');

    Route::get('top-up/admin', 'ViewController@topUpAdmin');
    Route::get('top-up/agent', 'ViewController@topUpAgent');
    Route::get('top-up/player', 'ViewController@topUpPlayer');

    Route::get('order/wechat', 'ViewController@orderWechat');
    Route::get('order/item', 'ViewController@orderItem');

    Route::get('system/log', 'ViewController@systemLog');
});

//代理商接口
Route::group([
    'middleware' => ['auth'],
    'prefix' => 'agent/api',
    'namespace' => 'Agent'
], function () {
    Route::put('self/info', 'AgentController@update');
    Route::put('self/password', 'AgentController@updatePass');
    Route::get('self/agent-type', 'AgentController@agentType');

    Route::post('stock', 'StockController@apply');
    Route::get('stock/history', 'StockController@applyHistory');

    Route::get('subagent', 'SubAgentController@show');
    Route::post('subagent', 'SubAgentController@create');
    Route::delete('subagent/{user}', 'SubAgentController@destroy')->where('user', '[0-9]+');
    Route::put('subagent/{child}', 'SubAgentController@updateChild')->where('child', '[0-9]+');

    Route::post('top-up/child/{receiver}/{type}/{amount}', 'TopUpController@topUp2Child')->where('amount', '[0-9]+');
    Route::post('top-up/player/{player}/{type}/{amount}', 'TopUpController@topUp2Player')->where('amount', '[0-9]+');
    Route::get('top-up/child', 'TopUpController@topUp2ChildHistory');
    Route::get('top-up/player', 'TopUpController@topUp2PlayerHistory');
});

//代理商视图
Route::group([
    'middleware' => ['auth'],
    'prefix' => 'agent',
    'namespace' => 'Agent'
], function () {
    Route::get('home', 'ViewController@home');

    Route::get('player/top-up', 'ViewController@playerTopUp');  //玩家充值页面

    Route::get('stock/apply-request', 'ViewController@stockApplyRequest');
    Route::get('stock/apply-history', 'ViewController@stockApplyHistory');

    Route::get('subagent/list', 'ViewController@subagentList');
    Route::get('subagent/create', 'ViewController@subagentCreate');

    //给子代理商的充值记录
    Route::get('top-up/child', 'ViewController@topUpChild');
    Route::get('top-up/player', 'ViewController@topUpPlayer');

    Route::get('info', 'ViewController@info');
});