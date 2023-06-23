<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\AstroV2;
use Illuminate\Console\Command;

class AstroV2Query extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'astro:v2query {id=1 : 星座索引ID} {type=1 : 查询模式 1今日 2明日 3本周 4本月}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '根据星座索引ID和查询模式获取今日、明日、本周、本月运势';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $id = $this->argument('id');
        $type = $this->argument('type');

        $now = Carbon::now();

        $dateArr = [
            'month' => $now->month,
            'today' => $now->toDateString(),
            'tomorrow' => $now->copy()->addDay()->toDateString(),
            'startOfWeek' => $now->copy()->startOfWeek()->toDateString(),
            'endOfWeek' => $now->copy()->endOfWeek()->toDateString(),
            'startOfMonth' => $now->copy()->startOfMonth()->toDateString(),
            'endOfMonth' => $now->copy()->endOfMonth()->toDateString()
        ];
        if ($type == 1) {
            $astro = AstroV2::query()
                ->where('type', 1)
                ->where('index', $id)
                ->where('date_start', $dateArr['today'])
                ->first();
            $this->line($astro->constellation . ' - 今日运势(' . $astro->date_start . ')');
        } else if ($type == 2) {
            $astro = AstroV2::query()
                ->where('type', 1)
                ->where('index', $id)
                ->where('date_start', $dateArr['tomorrow'])
                ->first();
            $this->line($astro->constellation . ' - 明日运势(' . $astro->date_start . ')');
        } else if ($type == 3) {
            $astro = AstroV2::query()
                ->where('type', 2)
                ->where('index', $id)
                ->where('date_start', $dateArr['startOfWeek'])
                ->where('date_end', $dateArr['endOfWeek'])
                ->first();
            $this->line($astro->constellation . ' - 本周运势(' . $astro->date_start . '~' . $astro->date_end . ')');
        } else if ($type == 4) {
            $astro = AstroV2::query()
                ->where('type', 3)
                ->where('index', $id)
                ->where('month', $dateArr['month'])
                ->first();
            $this->line($astro->constellation . ' - 本月运势(' . $astro->month . '月)');
        }
        if ($type < 3) {
            $this->line('今日短评: ' . $astro->daily_desc);
            $this->line('幸运数字: ' . $astro->lucky_number);
            $this->line('幸运颜色: ' . $astro->lucky_color);
            $this->line('幸运方位: ' . $astro->lucky_direction);
            $this->line('今日吉时: ' . $astro->lucky_time);
            $this->line('速配星座: ' . $astro->lucky_astro);
        }
        if ($type == 3) {
            $this->line('致胜技巧: ' . $astro->victory_desc);
            $this->line('爱情秘笈: ' . $astro->love_desc);
            $this->line('速配星座: ' . $astro->lucky_astro);
            $this->line('幸运日: ' . $astro->lucky_day);
            $this->line('幸运服装: ' . $astro->lucky_cloth);
            $this->line('幸运数字: ' . $astro->lucky_number);
        }
        if ($type == 4) {
            $this->line('本月优势: ' . $astro->month_advantage);
            $this->line('本月弱势: ' . $astro->month_disadvantage);
            $this->line('休闲解压: ' . $astro->month_motion);
            $this->line('贵人方位: ' . $astro->month_posho);
            $this->line('烦人星座: ' . $astro->month_annoying);
            $this->line('贴心星座: ' . $astro->month_intimate);
            $this->line('财神星座: ' . $astro->month_mammon);
        }
        $this->line('整体指数: ' . $astro->overall);
        $this->line('整体运势: ' . $astro->overall_desc);
        $this->line('爱情指数: ' . $astro->romance);
        $this->line('爱情运势: ' . $astro->romance_desc);
        $this->line('事(学)业指数: ' . $astro->workjob);
        $this->line('事(学)业运势: ' . $astro->workjob_desc);
        $this->line('财运指数: ' . $astro->money);
        $this->line('财运运势: ' . $astro->money_desc);
        return true;
    }
}
