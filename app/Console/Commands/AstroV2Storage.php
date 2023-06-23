<?php

namespace App\Console\Commands;

use DB;
use Carbon\Carbon;
use GuzzleHttp\Pool;
use App\Models\AstroV2;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Overtrue\PHPOpenCC\OpenCC;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\DomCrawler\Crawler;

class AstroV2Storage extends Command
{
    /**
     * 代理池
     */
    protected $proxies = [
        '58.147.186.227:3125',
        '117.69.233.240:8089',
        '85.206.175.161:3128',
        '45.189.113.142:999',
        '190.131.250.105:999'
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
    protected $signature = 'astro:v2';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '从click108采集今日、明日、本周、本月星座运势并入库';

    /**
     * Execute the console command.
     */
    public function handle()
    {

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

        $this->info('开始采集星座运势数据...');
        $this->info('Code by EllisFan <ellisfan07@gmail.com>');

        // 查询是否已存在今日数据
        $check_today = AstroV2::query()->where('type', 1)->where('date_start', $dateArr['today'])->first();

        if (empty($check_today)) {
            try {
                $this->gatherData(1, $dateArr);
            } catch (\Exception $e) {
                $this->error($e->getMessage());
                return;
            }
        } else {
            $this->newLine();
            $this->info($dateArr['today'] . '星座运势数据已存在，跳过采集...');
        }

        // 查询是否已存在明日数据
        $check_tomorrow = AstroV2::query()->where('type', 1)->where('date_start', $dateArr['tomorrow'])->first();

        if (empty($check_tomorrow)) {
            try {
                $this->gatherData(2, $dateArr);
            } catch (\Exception $e) {
                $this->error($e->getMessage());
                return;
            }
        } else {
            $this->newLine();
            $this->info($dateArr['tomorrow'] . '星座运势数据已存在，跳过采集...');
        }

        // 查询是否已存在本周数据
        $check_week = AstroV2::query()->where('type', 2)->where('date_start', $dateArr['startOfWeek'])->first();

        if (empty($check_week)) {
            try {
                $this->gatherData(3, $dateArr);
            } catch (\Exception $e) {
                $this->error($e->getMessage());
                return;
            }
        } else {
            $this->newLine();
            $this->info($dateArr['startOfWeek'] . ' ~ ' . $dateArr['endOfWeek'] . '星座运势数据已存在，跳过采集...');
        }

        // 查询是否已存在本月数据
        $check_month = AstroV2::query()->where('type', 3)->where('month', $dateArr['month'])->first();

        if (empty($check_month)) {
            try {
                $this->gatherData(4, $dateArr);
            } catch (\Exception $e) {
                $this->error($e->getMessage());
                return;
            }
        } else {
            $this->newLine();
            $this->info($dateArr['month'] . '月星座运势数据已存在，跳过采集...');
            $this->newLine();
            $this->info('星座运势已采集完毕！');
        }
        return true;
    }

    /**
     * Converts Traditional Chinese text to Simplified Chinese using OpenCC.
     *
     * @param string $text The text to be converted.
     * @return string The converted text.
     */
    private function tw2sp(string $text): string
    {
        return OpenCC::convert($text, 'TW2SP');
    }

    /**
     * Processes the astrological name based on the given type and text.
     *
     * @param int $type the type of astrological name to process
     * @param string $text the text to search for the astrological name
     * @return string the processed astrological name in simplified Chinese
     */
    private function processAstroName($type, string $text): string
    {
        $typeArr = [1 => '今日', 2 => '明日', 3 => '本周', 4 => '本月'];

        if (array_key_exists($type, $typeArr)) {
            preg_match_all('/'.$typeArr[$type].'(.+)解析/', $text, $name);
            $astro_name = $name[1][0];
        }

        if ($astro_name == '牡羊座') {
            $astro_name = '白羊座';
        }

        return OpenCC::convert($astro_name, 'TW2SP');
    }

    /**
     * 计算⭐️的数量
     *
     * @param string $text The text to be processed.
     * @return int The count of star symbols in the text.
     */
    private function processStar(string $text): int
    {
        preg_match_all("/★/", $text, $stars);
        return count($stars[0]);
    }

    /**
     * Creates a generator function that yields requests from a list of URLs and proxies.
     *
     * @param array $urls a list of URLs to generate requests from
     * @param array $proxies a list of proxies to use for the requests
     */
    private function createRequestsGenerator(array $urls, array $proxies)
    {
        yield from $this->generateRequests($urls, $proxies);
    }

    /**
     * Creates a generator that yields requests for each URL in the given array, using proxies from another array.
     *
     * @param array $urls Array of URL strings
     * @param array $proxies Array of proxy strings
     */
    private function generateRequests(array $urls, array $proxies)
    {
        foreach ($urls as $i => $url) {
            $proxyIndex = $i % count($proxies);
            yield new Request('GET', $url, [
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36',
                'Accept-Encoding' => 'gzip, deflate, br',
                'proxy' => $proxies[$proxyIndex]
            ]);
        }
    }

    /**
     * Builds an array of URLs based on the type of horoscope and date.
     *
     * @param int $type the type of horoscope (1 for today, 2 for tomorrow, 3 for this week, 4 for this month)
     * @param string $date the date in yyyy-mm-dd format
     * @return array an array of URLs for each horoscope
     */
    private function buildUrls($type, string $date): array
    {
        $urls = [];

        for ($i = 0; $i < count($this->astros); $i++) {
            $astroId = $i;
            $url = '';

            if ($type === 1) {
                // 今日运势
                $url = "http://astro.click108.com.tw/daily_{$astroId}.php?iAstro={$astroId}";
            } else if ($type === 2) {
                // 明日运势
                $url = "https://astro.click108.com.tw/daily_{$astroId}.php?iAstro={$astroId}&iAcDay={$date}&iType=4";
            } else if ($type === 3) {
                // 本周运势
                $url = "https://astro.click108.com.tw/weekly_{$astroId}.php?iAstro={$astroId}&iAcDay={$date}&iType=1";
            } else if ($type === 4) {
                // 本月运势
                $url = "https://astro.click108.com.tw/monthly_{$astroId}.php?&iAstro={$astroId}&iAcDay={$date}&iType=2";
            }

            $urls[] = $url;
        }

        return $urls;
    }


    /**
     * Sets an error log for a failed star sign collection.
     *
     * @param int $type Type of star sign collection failure.
     * @param int $index Index of failed star sign collection.
     * @param mixed $reason Reason for failed star sign collection.
     * @param array $dateArr Array of dates associated with the failed star sign collection.
     */
    private function setErrorLog($type, int $index, mixed $reason, array $dateArr)
    {
        if ($type === 1) {
            Log::error($dateArr['today'] . '星座运势采集失败: index => ' . $index . ', reason => ' . print_r($reason, true));
        } else if ($type === 2) {
            Log::error($dateArr['tomorrow'] . '星座运势采集失败: index => ' . $index . ', reason => ' . print_r($reason, true));
        } else if ($type === 3) {
            Log::error($dateArr['startOfWeek'] . ' ~ ' . $dateArr['endOfWeek'] . '星座运势采集失败: index => ' . $index . ', reason => ' . print_r($reason, true));
        } else if ($type === 4) {
            Log::error($dateArr['month'] . '月星座运势采集失败: index => ' . $index . ', reason => ' . print_r($reason, true));
        }
    }

    /**
     * Saves an array of contents to the database.
     *
     * @param mixed $contents an array of contents to save
     * @throws \Exception if the contents could not be saved
     */
    private function saveToDatabase($contents)
    {
        // 进行数据入库
        DB::beginTransaction();
        try {
            foreach ($contents as $content) {
                AstroV2::create($content);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->newLine();
            $message = '星座运势入库失败！error message: ' . $e->getMessage();
            Log::error($message);
            throw new \Exception($message);
        }
    }

    /**
     * gather data for the type of horoscope.
     *
     * @param int $type the type of horoscope to gather data for
     * @throws \Exception when the horoscope collection fails
     */
    private function gatherData($type, array $dateArr)
    {
        if ($type === 1) {
            $this->info('开始采集' . $dateArr['today'] . '星座运势');
        } else if ($type === 2) {
            $this->info('开始采集' . $dateArr['tomorrow'] . '星座运势');
        } else if ($type === 3) {
            $this->info('开始采集' . $dateArr['startOfWeek'] . ' ~ ' . $dateArr['endOfWeek'] . '星座运势');
        } else if ($type === 4) {
            $this->info('开始采集' . $dateArr['month'] . '月星座运势');
        }

        $urls = $this->buildUrls($type, $type === 2 ? $dateArr['tomorrow'] : $dateArr['today']);

        $contents = [];

        $client = new Client();

        $bar = $this->output->createProgressBar(count($urls));

        $bar->start();

        $pool = new Pool($client, $this->createRequestsGenerator($urls, $this->proxies), [
            'concurrency' => 2, // 这个数值表示最大并发请求的数量
            'fulfilled' => function ($response, $index) use (&$contents, $bar, $type, $dateArr) {
                // 请求成功后的回调函数
                $html = (string) $response->getBody();
                try {
                    $this->filterResponse($type, $html, $dateArr, $contents);
                } catch (\Exception $e) {
                    $this->newLine();
                    $this->error($e->getMessage());
                    // 如果采集报错了一定要查看日志
                    Log::error($e);
                    throw new \Exception('星座运势采集失败！');
                }
                $bar->advance();
            },
            'rejected' => function ($reason, $index) use ($type, $dateArr) {
                $this->setErrorLog($type, $index, $reason, $dateArr);
                throw new \Exception('星座运势采集失败，具体信息请查看日志！');
            },
        ]);

        $promise = $pool->promise();
        $promise->wait();

        $this->saveToDatabase($contents);

        $bar->finish();
        $this->newLine();
        if ($type === 1) {
            $this->info('采集' . $dateArr['today'] . '星座运势完成！');
        } else if ($type === 2) {
            $this->info('采集' . $dateArr['tomorrow'] . '星座运势完成！');
        } else if ($type === 3) {
            $this->info('采集' . $dateArr['startOfWeek'] . ' ~ ' . $dateArr['endOfWeek'] . '星座运势完成！');
        } else if ($type === 4) {
            $this->info('采集' . $dateArr['month'] . '月星座运势完成！');
            $this->info('全部星座运势采集入库完成！');
        }
        return true;
    }

    /**
     * Filters the response based on the type of astrology, HTML data, date array and contents array passed as references.
     *
     * @param int $type the type of astrology
     * @param string $html the HTML data
     * @param array $dateArr the date array
     * @param array $contents the contents array passed as reference
     */
    private function filterResponse($type, $html, $dateArr, &$contents)
    {
        $crawler = new Crawler($html);

        // 星座名称
        $name = $crawler->filter('.FORTUNE_BG .FORTUNE_RESOLVE .TODAY_CONTENT h3')->each(function (Crawler $node) use ($type) {
            return $this->processAstroName($type, $node->text());
        });

        // 一些短评
        $shorts = $crawler->filter('.TODAY_FORTUNE .TODAY_WORD p')->each(function (Crawler $node) {
            return $this->tw2sp($node->text());
        });

        // 幸运系列
        $lucky = $crawler->filter($type < 3 ? '.TODAY_LUCKY .LUCKY h4' : '.TODAY_LUCKY_2 .LUCKY h4')->each(function (Crawler $node) {
            return $this->tw2sp($node->text());
        });

        // 采集运势
        $fortunes = $crawler->filter('.FORTUNE_BG .FORTUNE_RESOLVE .TODAY_CONTENT p')->each(function (Crawler $node) {
            return $this->tw2sp($node->text());
        });

        // 获取星座索引id
        $index = array_search($name[0], $this->astros);
        $contents[$index]['type'] = $type < 3 ? 1 : $type - 1;
        $contents[$index]['index'] = $index;
        $contents[$index]['month'] = $dateArr['month'];
        if ($type < 3) {
            $contents[$index]['date_start'] = $type === 2 ? $dateArr['tomorrow'] : $dateArr['today'];
        }
        if ($type === 3) {
            $contents[$index]['date_start'] = $dateArr['startOfWeek'];
            $contents[$index]['date_end'] = $dateArr['endOfWeek'];
        }
        if ($type === 4) {
            $contents[$index]['date_start'] = $dateArr['startOfMonth'];
            $contents[$index]['date_end'] = $dateArr['endOfMonth'];
        }
        $contents[$index]['constellation'] = $name[0];
        // 今日、明日短评
        if ($type === 1 || $type === 2) {
            $contents[$index]['daily_desc'] = $shorts[0];
        }
        // 本周致胜、爱情技巧
        if ($type === 3) {
            $contents[$index]['victory_desc'] = $shorts[0];
            $contents[$index]['love_desc'] = $shorts[1];
        }
        // 本月优势、本月弱势
        if ($type === 4) {
            $contents[$index]['month_advantage'] = $shorts[0];
            $contents[$index]['month_disadvantage'] = $shorts[0];
        }
        if ($type < 3) {
            // 幸运系列
            $contents[$index]['lucky_number'] = $lucky[0];
            $contents[$index]['lucky_color'] = $lucky[1];
            $contents[$index]['lucky_direction'] = $lucky[2];
            $contents[$index]['lucky_time'] = $lucky[3];
            $contents[$index]['lucky_astro'] = $lucky[4] == '牡羊座' ? '白羊座' : $lucky[4];
            // 整体运势
            $contents[$index]['overall'] = $this->processStar($fortunes[0]);
            $contents[$index]['overall_desc'] = $fortunes[1];
            // 爱情运势
            $contents[$index]['romance'] = $this->processStar($fortunes[2]);
            $contents[$index]['romance_desc'] = $fortunes[3];
            // 事(学)业运势
            $contents[$index]['workjob'] = $this->processStar($fortunes[4]);
            $contents[$index]['workjob_desc'] = $fortunes[5];
            // 财运运势
            $contents[$index]['money'] = $this->processStar($fortunes[6]);
            $contents[$index]['money_desc'] = $fortunes[7];
        }
        if ($type === 3) {
            // 幸运系列
            $contents[$index]['lucky_day'] = $lucky[0];
            $contents[$index]['lucky_cloth'] = $lucky[1];
            $contents[$index]['lucky_number'] = $lucky[2];
            // 本周整体运势
            $contents[$index]['overall'] = $this->processStar($fortunes[0]);
            $contents[$index]['overall_desc'] = $fortunes[1];
            // 本周爱情运势
            $contents[$index]['romance'] = $this->processStar($fortunes[2]);
            $contents[$index]['romance_desc'] = $fortunes[3];
            preg_match('/(?<=速配星座：)\p{Han}+/u', $fortunes[5], $lucky_astro);
            if (!empty($lucky_astro)) {
                $contents[$index]['lucky_astro'] = $lucky_astro[0];
            }
            // 本周事(学)业运势
            $contents[$index]['workjob'] = $this->processStar($fortunes[6]);
            $contents[$index]['workjob_desc'] = $fortunes[7];
            // 本周财运运势
            $contents[$index]['money'] = $this->processStar($fortunes[8]);
            $contents[$index]['money_desc'] = $fortunes[9];
        }
        if ($type === 4) {
            // 幸运系列
            $contents[$index]['month_motion'] = $lucky[0];
            $contents[$index]['month_posho'] = $lucky[1];
            $contents[$index]['month_annoying'] = $lucky[2] == '牡羊座' ? '白羊座' : $lucky[2];
            $contents[$index]['month_intimate'] = $lucky[3] == '牡羊座' ? '白羊座' : $lucky[3];
            $contents[$index]['month_mammon'] = $lucky[4] == '牡羊座' ? '白羊座' : $lucky[4];
            // 本月整体运势
            $contents[$index]['overall'] = $this->processStar($fortunes[0]);
            $contents[$index]['overall_desc'] = $fortunes[1];
            // 本月爱情运势
            $contents[$index]['romance'] = $this->processStar($fortunes[2]);
            $contents[$index]['romance_desc'] = strip_tags($fortunes[4]) . '\r\n' . strip_tags($fortunes[5]);
            // 本月事(学)业运势
            $contents[$index]['workjob'] = $this->processStar($fortunes[6]);
            $contents[$index]['workjob_desc'] = strip_tags($fortunes[8]) . '\r\n' . strip_tags($fortunes[9]);
            // 本月财运运势
            $contents[$index]['money'] = $this->processStar($fortunes[10]);
            $contents[$index]['money_desc'] = $fortunes[11];
        }
    }
}
