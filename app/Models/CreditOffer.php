<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditOffer extends Model
{
    use HasFactory;

    protected $table = 'credit_offers';

    protected $fillable = ['cpf', 'instituicaoFinanceira', 'modalidadeCredito', 'valorAPagar', 'valorSolicitado', 'taxaJuros', 'qntParcelas'];
}
