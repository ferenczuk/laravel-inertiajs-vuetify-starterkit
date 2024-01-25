<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVendorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vendors', function (Blueprint $table) {
            
            $table->bigIncrements('id');
            $table->integer('status');

            $table->string('name')->unique();
            $table->string('domain')->unique();
            $table->string('domain_tenant_trial')->nullable();
            $table->string('url_helpers')->nullable();
            $table->string('default_pass')->nullable();
            $table->string('logo_login')->nullable();
            $table->string('bg_login')->nullable();
            $table->string('icon_admin')->nullable();
            $table->string('logo_admin')->nullable();
            $table->string('bg_logo_admin')->nullable();
            $table->string('primary_color')->nullable();
            $table->string('secondary_color')->nullable();
           
            $table->string('corporate_name')->unique();
            $table->string('corporate_number', 20)->unique();
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('address')->nullable();
            $table->string('number')->nullable();
            $table->string('complement')->nullable();
            $table->string('bairro')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 2)->nullable();
            $table->string('country')->default('Brasil');
            $table->text('info')->nullable();
            $table->date('next_invoice')->nullable();
            $table->date('not_suspend')->nullable();
            $table->string('payment_method')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vendors');
    }
}
