<?php

namespace App\Console\Commands;

use App\Console\BaseCommand;
use App\Models\StatementDaily;
use App\Services\StatisticsService;
use Carbon\Carbon;

class GenerateDailyStatement extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:generate-daily-statement {--date= : date time}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成每日数据报表';


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $options = $this->options();
        $date = $this->transDate($options['date']);
        $this->generateDailyStatement($date);
    }

    public function generateDailyStatement($date)
    {
        $statisticsService = app(StatisticsService::class);
        $data = [
            'date' => $date,
            'average_online_players' => $statisticsService->getAverageOnlinePlayersCount($date),
            'peak_online_players' => $statisticsService->getPeakOnlinePlayersAmount($date),
            'active_players' => $statisticsService->getActivePlayersAmount($date),
            'incremental_players' => $statisticsService->getIncrementalPlayersAmount($date),
            'one_day_remained' => $statisticsService->getRemainedData($date, 1),
            'one_week_remained' => $statisticsService->getRemainedData($date, 7),
            'two_weeks_remained' => $statisticsService->getRemainedData($date, 14),
            'one_month_remained' => $statisticsService->getRemainedData($date, 30),
            'card_consumed_data' => $statisticsService->getCardConsumedData($date),
            'card_bought_data' => $statisticsService->getCardBoughtData($date),
            'card_consumed_sum' => $statisticsService->getCardConsumedSum($date),
            'card_bought_sum' => $statisticsService->getCardBoughtSum($date),

        ];

        if ($result = $this->ifRecordExist($date)) {
            $result->update($data);
            $this->logInfo("{$date} : 数据报表覆盖完成");

            return true;
        }
        StatementDaily::create($data);
        $this->logInfo("{$date}: 数据报表生成完成。");

        return true;
    }

    protected function transDate($date)
    {
        //如果为空则 找上个月的数据
        if (empty($date)) {
            $date = Carbon::yesterday()->format('Y-m-d');
        } else {
            $date = Carbon::parse($date)->format('Y-m-d');
        }

        return $date;
    }

    protected function ifRecordExist($date)
    {
        return StatementDaily::query()->whereDate('date', $date)->first();
    }
}
