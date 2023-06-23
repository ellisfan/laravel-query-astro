<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('astro_v2', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('type')->index()->default(1)->comment('1=>日运势 2=>周运势 3=>月运势');
            $table->unsignedTinyInteger('index')->index()->comment('索引');
            $table->unsignedTinyInteger('month')->index()->comment('月份');
            $table->date('date_start')->index()->comment('日期');
            $table->date('date_end')->nullable()->comment('日期');
            $table->string('constellation', 3)->comment('星座');
            $table->string('daily_desc')->nullable()->comment('每日短评');

            $table->string('victory_desc')->nullable()->comment('致胜技巧');
            $table->string('love_desc')->nullable()->comment('爱情秘笈');

            $table->string('month_advantage')->nullable()->comment('本月优势');
            $table->string('month_disadvantage')->nullable()->comment('本月弱势');
            $table->string('month_motion')->nullable()->comment('休闲解压');
            $table->string('month_posho')->nullable()->comment('贵人方位');
            $table->string('month_annoying')->nullable()->comment('烦人星座');
            $table->string('month_intimate')->nullable()->comment('贴心星座');
            $table->string('month_mammon')->nullable()->comment('财神星座');

            $table->string('lucky_day')->nullable()->comment('幸运日');
            $table->string('lucky_cloth')->nullable()->comment('幸运服装');
            $table->string('lucky_number')->nullable()->comment('幸运数字');
            $table->string('lucky_color')->nullable()->comment('幸运颜色');
            $table->string('lucky_direction')->nullable()->comment('幸运方位');
            $table->string('lucky_time')->nullable()->comment('每日吉时');
            $table->string('lucky_astro')->nullable()->comment('速配星座');

            $table->string('overall')->nullable()->comment('整体指数');
            $table->text('overall_desc')->nullable()->comment('整体运势解读');

            $table->string('romance')->nullable()->comment('爱情指数');
            $table->text('romance_desc')->nullable()->comment('爱情运势解读');

            $table->string('workjob')->nullable()->comment('事(学)业指数');
            $table->text('workjob_desc')->nullable()->comment('事(学)业运势解读');

            $table->string('money')->nullable()->comment('财运指数');
            $table->text('money_desc')->nullable()->comment('财运运势解读');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('astro_v2');
    }
};
