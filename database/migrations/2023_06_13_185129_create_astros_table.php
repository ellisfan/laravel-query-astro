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
        Schema::create('astros', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('index')->index()->comment('索引');
            $table->date('date')->index()->comment('日期');
            $table->string('constellation', 3)->comment('星座');
            $table->string('daily_desc')->nullable()->comment('每日短评');

            $table->string('lucky_number')->nullable()->comment('幸运数字');
            $table->string('lucky_color')->nullable()->comment('幸运颜色');
            $table->string('lucky_direction')->nullable()->comment('幸运方位');
            $table->string('lucky_time')->nullable()->comment('每日吉时');
            $table->string('lucky_astro')->nullable()->comment('速配星座');

            $table->string('overall')->nullable()->comment('整体指数');
            $table->string('overall_desc')->nullable()->comment('整体运势解读');

            $table->string('romance')->nullable()->comment('爱情指数');
            $table->string('romance_desc')->nullable()->comment('爱情运势解读');

            $table->string('workjob')->nullable()->comment('事(学)业指数');
            $table->string('workjob_desc')->nullable()->comment('事(学)业运势解读');

            $table->string('money')->nullable()->comment('财运指数');
            $table->string('money_desc')->nullable()->comment('财运运势解读');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('astros');
    }
};
