<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCreditOffersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('credit_offers', function (Blueprint $table) {
            $table->id();
            $table->string('cpf');
            $table->string('instituicaoFinanceira');
            $table->string('modalidadeCredito');
            $table->float('valorAPagar');
            $table->float('valorSolicitado');
            $table->float('taxaJuros');
            $table->integer('qntParcelas');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('credit_offers');
    }
}
