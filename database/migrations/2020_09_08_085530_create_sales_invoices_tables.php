<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesInvoicesTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales_invoices', function (Blueprint $table) {
            $table->bigInteger('id')->unsigned()->primary();
            $table->string('number')->unique();
            $table->date('date');
            $table->foreignId('customer_id');
            $table->string('customer_name');
            $table->text('description')->nullable();
            $table->timestamps();
        });


        Schema::create('sales_invoice_items', function (Blueprint $table) {
            $table->bigInteger('id')->unsigned()->primary();
            $table->foreignId('sales_invoice_id');
            $table->foreignId('item_id');
            $table->string('name');
            $table->double('quantity');
            $table->double('price');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('sales_invoice_id')
                ->references('id')
                ->on('sales_invoices')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sales_invoices');
        Schema::dropIfExists('sales_invoice_items');
    }
}
