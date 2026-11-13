<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Femea extends Model
{
    use HasFactory;

    protected $table = 'femea';

    const CREATED_AT = 'criado_em';

    const UPDATED_AT = 'atualizado_em';

    const INATIVIDADES = ['morte', 'descarte', 'venda'];

    protected $fillable = [
        'id_primaria',
        'id_secundaria',
        'tipo_compra',
        'data_nascimento',
        'data_compra',
        'ciclos_ate_compra',
        'data_cobertura',
        'raca_id',
        'valor_compra',
        'peso_compra',
        'peso_atual',
        'fornecedor_id',
        'caracteristicas',
        'localizacao',
        'baia',
    ];

    protected $casts = [
        'data_nascimento' => 'date',
        'data_compra' => 'date',
        'data_cobertura' => 'date',
        'valor_compra' => 'decimal:2',
        'peso_compra' => 'decimal:2',
        'peso_atual' => 'decimal:2',
        'ciclos_ate_compra' => 'integer',
    ];

    public function raca()
    {
        return $this->belongsTo(Raca::class, 'raca_id');
    }

    public function fornecedor()
    {
        return $this->belongsTo(Fornecedor::class, 'fornecedor_id');
    }

    public function movimentos()
    {
        return $this->hasMany(FemeaMovimento::class, 'femea_id');
    }

    /**
     * Retorna a quantidade total de fêmeas ATIVAS no momento.
     * Uma fêmea é considerada ativa se sua ÚLTIMA movimentação NÃO
     * for morte, descarte ou venda.
     */
    public static function countAtivas(): int
    {
        if (! Schema::hasTable('femea') || ! Schema::hasTable('femea_movimento')) {
            return 0;
        }

        $ultimaMov = DB::table('femea_movimento as fm')
            ->selectRaw('fm.femea_id, MAX(fm.id) as ultimo_id')
            ->groupBy('fm.femea_id');

        return (int) DB::table('femea as f')
            ->leftJoinSub($ultimaMov, 'um', 'um.femea_id', '=', 'f.id')
            ->leftJoin('femea_movimento as fm2', 'fm2.id', '=', 'um.ultimo_id')
            ->whereNull('fm2.acao')
            ->orWhere(function ($q) {
                $q->whereNotIn('fm2.acao', self::INATIVIDADES);
            })
            ->count('f.id');
    }

    /**
     * Retorna o limite de fêmeas ativas cadastrado para o cliente.
     * NULL ou 0 = sem limite.
     */
    public static function getLimiteAtivas(): ?int
    {
        if (Schema::hasTable('configuracoes') && Schema::hasColumn('configuracoes', 'limite_femeas')) {
            $raw = DB::table('configuracoes')->orderBy('id')->value('limite_femeas');
        } else {
            return null;
        }

        if ($raw === null || trim((string) $raw) === '') {
            return null;
        }

        $v = (int) $raw;

        return $v > 0 ? $v : null;
    }

    /**
     * Valida se é permitido adicionar novas fêmeas sem ultrapassar o limite ativo.
     * Retorna array com chaves:
     *   'permitido' => bool
     *   'limite' => int|null
     *   'ativas_atuais' => int
     *   'ativas_apos' => int
     *   'mensagem' => string|null
     */
    public static function validarLimiteAtivas(int $quantidadeNovas = 1, bool $bloquearConfiguracao = false): array
    {
        if ($bloquearConfiguracao
            && Schema::hasTable('configuracoes')
            && Schema::hasColumn('configuracoes', 'limite_femeas')) {
            DB::table('configuracoes')->orderBy('id')->lockForUpdate()->first();
        }

        $limite = self::getLimiteAtivas();
        $ativas = self::countAtivas();

        $result = [
            'permitido' => true,
            'limite' => $limite,
            'ativas_atuais' => $ativas,
            'ativas_apos' => $ativas + max(0, $quantidadeNovas),
            'mensagem' => null,
        ];

        if ($limite === null) {
            return $result;
        }

        if ($result['ativas_apos'] > $limite) {
            $excedente = $result['ativas_apos'] - $limite;
            $textoNovas = $quantidadeNovas === 1
                ? 'esta nova fêmea'
                : "estas {$quantidadeNovas} novas fêmeas";
            $textoExcedente = $excedente === 1
                ? '1 fêmea acima do permitido'
                : "{$excedente} fêmeas acima do permitido";
            $result['permitido'] = false;
            $result['mensagem'] = "Limite de fêmeas ativas atingido. Permitido: {$limite}. Atualmente existem {$ativas} fêmea(s) ativa(s). Incluir {$textoNovas} deixará o total em {$result['ativas_apos']} ({$textoExcedente}). Faça um descarte, venda ou morte antes de cadastrar novas fêmeas.";
        }

        return $result;
    }
}
