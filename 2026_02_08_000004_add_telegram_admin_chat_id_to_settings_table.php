<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTelegramAdminChatIdToSettingsTable extends Migration
{
    public function up()
    {
        Schema::table('settings', function (Blueprint $table) {
            if (!Schema::hasColumn('settings', 'telegram_admin_chat_id')) {
                $table->string('telegram_admin_chat_id')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('settings', function (Blueprint $table) {
            if (Schema::hasColumn('settings', 'telegram_admin_chat_id')) {
                $table->dropColumn('telegram_admin_chat_id');
            }
        });
    }
}
