# laravel-query-astro
Laravel采集每日星座运势，由于目标语言是台湾正文，所以使用[Overtrue\PHPOpenCC](https://github.com/overtrue/php-opencc)转为简体入库

## 安装

```shell
$ cp .env.example .env

$ php artisan key:gen

$ composer install
```
## 使用

#### 1. 采集入库

```shell
$ php artisan astro:storage
12/12 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓] 100%
2023-06-13星座运势采集入库完成！
```
#### 2. 查询

```shell
$ php artisan astro:query 4
日期: 2023-06-13
星座: 狮子座
今日短评: 只要充满干劲，方可诸事顺遂。
幸运数字: 0
幸运颜色: 珊瑚橙
幸运方位: 正东方向
今日吉时: 3:00-4:00pm
速配星座: 天秤座
整体指数: 4
整体运势: 工作热情高涨，效率也高，没有丝毫疲累的感觉。审美观不错，买份小礼物送给另一半可令对方感动，对感情有增进作用。财运不太稳定，应谋定而后动，不可急于求成。
爱情指数: 3
爱情运势: 找情人去大吃一顿，借着吃饭擡杠一下吧！
事(学)业指数: 4
事(学)业运势: 事业运不错，可以大胆的尝试不敢做之事；主动一点，积极一点，会有正面的进展。
财运指数: 3
财运运势: 今天在金钱运作上显得特别大胆、有魄力，虽然眼光远大，但还是要仔细评估后才可以有大动作喔！
```

#### 3. 星座索引

```php
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
```

#### 4. 每日自动采集

在app/Console/Kernel.php中加入
```php
protected function schedule(Schedule $schedule): void
{
    $schedule->command('astro:storage')->daily();
}
```

## License

MIT
