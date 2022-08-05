<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CreditOffer;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class CreditOfferController extends Controller
{
    public function getCreditOffer(Request $request)
    {
        if (!Arr::get($request, 'cpf') || !Arr::get($request, 'valorSolicitado')) {
            return response()->json([
                'code' => 400,
                'message' => 'Verifique as informações'
            ]);
        }

        $data = [
            'cpf' => preg_replace('/\D/', '', Arr::get($request, 'cpf')),
            'value' => Arr::get($request, 'valorSolicitado')
        ];

        $this->getCredit($data);

        $bestOffers = $this->bestOffers(Arr::get($data, 'cpf'), Arr::get($data, 'value'));

        if (count($bestOffers) === 0) {
            return response()->json([
                'code' => 200,
                'message' => 'Nenhuma oferta encontrada.'
            ]);
        }

        return response()->json([
            'code' => 200,
            'offers' => $bestOffers
        ]);
    }

    private function getCredit($request)
    {
        $cpf = preg_replace('/\D/', '', Arr::get($request, 'cpf'));
        $response = Http::post('https://dev.gosat.org/api/v1/simulacao/credito', [
            'cpf' => $cpf
        ])->json();

        $offers = [];
        foreach ($response['instituicoes'] as $instituicao) {
            foreach ($instituicao['modalidades'] as $modalidade) {
                $offers[$instituicao['nome']][$modalidade['nome']][] = $this->getOffer($cpf, $instituicao['id'], $modalidade['cod']);
            }
        }
        $this->storeCreditOffer($offers, $cpf, Arr::get($request, 'value'));
    }

    private function getOffer($cpf, $instId, $codModalidade)
    {
        return Http::post('https://dev.gosat.org/api/v1/simulacao/oferta', [
            'cpf' => $cpf,
            'instituicao_id' => $instId,
            'codModalidade' => $codModalidade
        ])->json();
    }

    private function storeCreditOffer($offers, $cpf, $value)
    {
        foreach ($offers as $instituicaoFinanceira => $offer) {
            foreach ($offer as $modalidadeCredito => $o) {
                if (($value <= $o[0]['valorMin']) || ($value >= $o[0]['valorMin'] && $value <= $o[0]['valorMax'])) {
                    $creditOffer =  CreditOffer::query()
                        ->where('cpf', $cpf)
                        ->where('valorSolicitado', $value)
                        ->where('instituicaoFinanceira', $instituicaoFinanceira)
                        ->where('modalidadeCredito', $modalidadeCredito)
                        ->first()
                        ?? new CreditOffer();
                    $creditOffer->cpf = $cpf;
                    $creditOffer->instituicaoFinanceira = $instituicaoFinanceira;
                    $creditOffer->modalidadeCredito = $modalidadeCredito;
                    $creditOffer->valorAPagar = $value * pow((1 + $o[0]['jurosMes']), $o[0]['QntParcelaMin']);
                    $creditOffer->taxaJuros = $o[0]['jurosMes'];
                    $creditOffer->qntParcelas = $o[0]['QntParcelaMin'];
                    $creditOffer->valorSolicitado  = $value;
                    $creditOffer->save();
                }
            }
        }
    }

    private function bestOffers($cpf, $value)
    {
        return CreditOffer::query()
            ->select('instituicaoFinanceira', 'modalidadeCredito', 'valorAPagar', 'valorSolicitado', 'taxaJuros', 'qntParcelas')
            ->where('cpf', $cpf)
            ->where('valorSolicitado', $value)
            ->orderBy('valorAPagar')->limit(3)->get()->toArray();
    }
}
