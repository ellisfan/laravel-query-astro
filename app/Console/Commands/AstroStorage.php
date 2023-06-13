<?php

namespace App\Console\Commands;

use DB;
use Carbon\Carbon;
use GuzzleHttp\Pool;
use App\Models\Astro;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Overtrue\PHPOpenCC\OpenCC;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class AstroStorage extends Command
{
    protected $urls = [
        'http://astro.click108.com.tw/daily_0.php?iAstro=0',
        'http://astro.click108.com.tw/daily_1.php?iAstro=1',
        'http://astro.click108.com.tw/daily_2.php?iAstro=2',
        'http://astro.click108.com.tw/daily_3.php?iAstro=3',
        'http://astro.click108.com.tw/daily_4.php?iAstro=4',
        'http://astro.click108.com.tw/daily_5.php?iAstro=5',
        'http://astro.click108.com.tw/daily_6.php?iAstro=6',
        'http://astro.click108.com.tw/daily_7.php?iAstro=7',
        'http://astro.click108.com.tw/daily_8.php?iAstro=8',
        'http://astro.click108.com.tw/daily_9.php?iAstro=9',
        'http://astro.click108.com.tw/daily_10.php?iAstro=10',
        'http://astro.click108.com.tw/daily_11.php?iAstro=11',
    ];

    protected $astros = [
        0 => "白羊座",
        1 => "金牛座",
        2 => "双子座",
        3 => "巨蟹座",
        4 => "狮子座",
        5 => "处女座",
        6 => "天秤座",
        7 => "天蝎座",
        8 => "射手座",
        9 => "摩羯座",
        10 => "水瓶座",
        11 => "双鱼座"
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'astro:storage';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '从click108采集今日星座运势并入库';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $client = new Client();

        $contents = [];

        $date = Carbon::now()->toDateString();

        $bar = $this->output->createProgressBar(count($this->urls));

        $bar->start();

        $requests = function ($urls) {
            foreach ($urls as $url) {
                yield new Request('GET', $url, [
                    'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36',
                    'Accept-Encoding' => 'gzip, deflate, br'
                ]);
            }
        };

        $pool = new Pool($client, $requests($this->urls), [
            'concurrency' => 4, // 这个数值表示最大并发请求的数量
            'fulfilled' => function ($response, $index) use (&$contents, $bar, $date) {
                // 请求成功后的回调函数
                $html = (string) $response->getBody();
                $crawler = new Crawler($html);

                // 采集星座名称
                $constellation = $crawler->filter('.FORTUNE_BG .FORTUNE_RESOLVE .TODAY_CONTENT h3')->each(function (Crawler $node) {
                    preg_match_all('/今日(.+)解析/', $node->text(), $text);
                    $name = $text[1][0];
                    if ($name == '牡羊座') {
                        $name = '白羊座';
                    }
                    return OpenCC::convert($name, 'TW2SP');
                });
                // 采集今日短评
                $daily_desc = $crawler->filter('.TODAY_FORTUNE .TODAY_WORD p')->each(function (Crawler $node) {
                    return OpenCC::convert($node->text(), 'TW2SP');
                });
                // 采集今日幸运
                $lucky = $crawler->filter('.TODAY_LUCKY .LUCKY h4')->each(function (Crawler $node) {
                    return OpenCC::convert($node->text(), 'TW2SP');
                });
                // 采集今日运势
                $today = $crawler->filter('.FORTUNE_BG .FORTUNE_RESOLVE .TODAY_CONTENT p')->each(function (Crawler $node) {
                    return OpenCC::convert($node->text(), 'TW2SP');
                });
                // 获取星座索引id
                $index = array_search($constellation[0], $this->astros);
                // 今日星座
                $contents[$index]['index'] = $index;
                $contents[$index]['date'] = $date;
                $contents[$index]['constellation'] = $constellation[0];
                // 今日短评
                $contents[$index]['daily_desc'] = $daily_desc[0];
                // 今日幸运系列
                $contents[$index]['lucky_number'] = $lucky[0];
                $contents[$index]['lucky_color'] = $lucky[1];
                $contents[$index]['lucky_direction'] = $lucky[2];
                $contents[$index]['lucky_time'] = $lucky[3];
                $contents[$index]['lucky_astro'] = $lucky[4] == '牡羊座' ? '白羊座' : $lucky[4];
                // 今日整体运势
                preg_match_all("/★/", $today[0], $overall);
                $contents[$index]['overall'] = count($overall[0]);
                $contents[$index]['overall_desc'] = $today[1];
                // 今日爱情运势
                preg_match_all("/★/", $today[2], $romance);
                $contents[$index]['romance'] = count($romance[0]);
                $contents[$index]['romance_desc'] = $today[3];
                // 今日事(学)业运势
                preg_match_all("/★/", $today[4], $workjob);
                $contents[$index]['workjob'] = count($workjob[0]);
                $contents[$index]['workjob_desc'] = $today[5];
                // 今日财运运势
                preg_match_all("/★/", $today[6], $money);
                $contents[$index]['money'] = count($money[0]);
                $contents[$index]['money_desc'] = $today[7];

                $bar->advance();
            },
            'rejected' => function ($reason, $index) use ($date) {
                $this->newLine();
                $this->error($date . '星座运势采集失败，具体信息请查看日志！');
                Log::error($date . '星座运势采集失败: index => ' . $index . ', reason => ' . print_r($reason, true));
                return false;
            },
        ]);

        $promise = $pool->promise();
        $promise->wait();

        // 进行数据入库
        DB::beginTransaction();
        try {
            // 查询是否已存在今日数据
            $check = Astro::query()->where('date', $date)->first();

            if (!empty($check)) {
                Astro::query()->where('date', $date)->delete();
            }

            foreach ($contents as $content) {
                Astro::create($content);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->newLine();
            $this->error($date . '星座运势入库失败！error message: ' . $e->getMessage());
            Log::error($date . '星座运势入库失败！error message: ' . $e->getMessage());
            return false;
        }
        $bar->finish();
        $this->newLine();
        $this->info($date . '星座运势采集入库完成！');
        return true;
    }
}
