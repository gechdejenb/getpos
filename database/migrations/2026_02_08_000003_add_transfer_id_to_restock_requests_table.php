<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTransferIdToRestockRequestsTable extends Migration
{
    public function up()
    {
        Schema::table('restock_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('transfer_id')->nullable()->after('approved_by');
            $table->index('transfer_id');
        });
    }

    public function down()
    {
        Schema::table('restock_requests', function (Blueprint $table) {
            $table->dropIndex(['transfer_id']);
            $table->dropColumn('transfer_id');
        });
    }
}
