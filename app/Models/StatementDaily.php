<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatementDaily extends Model
{
    protected $table = 'statement_daily';
    protected $fillable = [
        'date', 'average_online_players', 'peak_online_players', 'peak_in_game_players', 'active_players',
        'incremental_players', 'one_day_remained', 'one_week_remained', 'two_weeks_remained', 'one_month_remained',
        'card_consumed_data', 'card_bought_data', 'card_consumed_sum', 'card_bought_sum',
    ];
}
