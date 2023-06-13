<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Astro;
use Illuminate\Console\Command;

class AstroQuery extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'astro:query {id : 星座索引ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '根据星座索引ID获取今日运势';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $id = $this->argument('id');
        $date = Carbon::now()->toDateString();
        $astro = Astro::query()->where('index', $id)->where('date', $date)->first();
        $this->line('日期: ' . $astro->date);
        $this->line('星座: ' . $astro->constellation);
        $this->line('今日短评: ' . $astro->daily_desc);
        $this->line('幸运数字: ' . $astro->lucky_number);
        $this->line('幸运颜色: ' . $astro->lucky_color);
        $this->line('幸运方位: ' . $astro->lucky_direction);
        $this->line('今日吉时: ' . $astro->lucky_time);
        $this->line('速配星座: ' . $astro->lucky_astro);
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
