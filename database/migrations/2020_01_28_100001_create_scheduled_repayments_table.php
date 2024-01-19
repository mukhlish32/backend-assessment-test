<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScheduledRepaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable("scheduled_repayments")) {
            Schema::create('scheduled_repayments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('loan_id');
    
                // TODO: Add missing columns here
                $table->integer('amount');
                $table->integer('outstanding_amount');
                $table->string('currency_code');
                $table->date('due_date');
                $table->string('status');
    
                $table->timestamps();
                $table->softDeletes();
    
                $table->foreign('loan_id')
                    ->references('id')
                    ->on('loans')
                    ->onUpdate('cascade')
                    ->onDelete('restrict');
            });
            Schema::enableForeignKeyConstraints();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('scheduled_repayments');
        Schema::enableForeignKeyConstraints();
    }
}
