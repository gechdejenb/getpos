<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRestockRequestsTable extends Migration
{
    public function up()
    {
        Schema::create('restock_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('from_warehouse_id');
            $table->unsignedBigInteger('to_warehouse_id');
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->string('status')->default('pending');
            $table->string('telegram_token', 32)->nullable()->index();
            $table->date('date')->nullable();
            $table->text('notes')->nullable();
            $table->json('items');
            $table->timestamps();

            $table->index(['from_warehouse_id', 'to_warehouse_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('restock_requests');
    }
}
