<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Serviço central de controle de acesso baseado em classe + permissões granulares.
 *
 * Três perfis (classes de usuário) — ordem de prioridade:
 *   1. 'administrador' -> TUDO liberado SEM CHECAR (incluir, alterar, excluir, acessar, metas, usuários).
 *   2. 'operador'      -> Regra DEFAULT LIBERADA. Se a lista de permissões for VAZIA (ou nula)
 *                         — assume que pode TUDO (escrita+leitura). Se existir permissões gravadas
 *                         no array, essas são BLOQUEADAS (deny list / lista negra).
 *   3. 'leitor'        -> Regra DEFAULT BLOQUEADA para escrita. Se a lista for VAZIA (ou nula),
 *                         só pode LER (ações de leitura/navegação). Se existir permissões gravadas,
 *                         essas são PERMITIDAS (allow list / lista branca).
 *
 * Estrutura da árvore de permissões:
 *   manejos.plantel.ver | manejos.plantel.escrever (incluir/editar/excluir).
 *   manejos.plantel.lancamentos.*
 *   manejos.gestacao.*
 *   manejos.maternidade.*
 *   manejos.producao.*
 *   analises.ver | analises.relatorios.*
 *   sistema.usuarios (SÓ admin deve ter)
 *   sistema.metas    (SÓ admin deve ter)
 */
class PermissaoService
{
    public const PERFIL_ADMIN = 'administrador';
    public const PERFIL_OPERADOR = 'operador';
    public const PERFIL_LEITOR = 'leitor';

    public static function perfisDisponiveis(): array
    {
        return [
            self::PERFIL_ADMIN    => 'Administrador',
            self::PERFIL_OPERADOR => 'Operador',
            self::PERFIL_LEITOR   => 'Leitor',
        ];
    }

    /**
     * Árvore completa de permissões disponíveis (usada para renderizar a árvore no modal).
     */
    public static function arvorePermissoes(): array
    {
        return [
            [
                'id' => 'manejos',
                'titulo' => 'Manejos',
                'icone' => 'fa-solid fa-warehouse',
                'children' => [
                    [
                        'id' => 'plantel',
                        'titulo' => 'Plantel Reprodutivo',
                        'icone' => 'fa-solid fa-cow',
                        'children' => [
                            [
                                'id' => 'plantel.lancamentos',
                                'titulo' => 'Lançamentos',
                                'icone' => 'fa-solid fa-pen-to-square',
                                'children' => [
                                    ['id' => 'plantel.lancamentos.femeas',         'titulo' => 'Fêmeas',                             'icone' => 'fa-solid fa-cow'],
                                    [
                                        'id' => 'plantel.lancamentos.compras',
                                        'titulo' => 'Compras',
                                        'icone' => 'fa-solid fa-cart-shopping',
                                        'children' => [
                                            ['id' => 'plantel.lancamentos.compras.leitoas',      'titulo' => 'Leitoas',          'icone' => 'fa-solid fa-seedling'],
                                            ['id' => 'plantel.lancamentos.compras.matriz_vazia', 'titulo' => 'Matriz Vazia',     'icone' => 'fa-solid fa-droplet'],
                                            ['id' => 'plantel.lancamentos.compras.matriz_gestante', 'titulo' => 'Matriz Gestante', 'icone' => 'fa-solid fa-baby-carriage'],
                                        ],
                                    ],
                                    ['id' => 'plantel.lancamentos.machos',         'titulo' => 'Machos (entrada, cadastro)',          'icone' => 'fa-solid fa-mars'],
                                    ['id' => 'plantel.lancamentos.saidas',         'titulo' => 'Saídas (morte, venda, descarte)',     'icone' => 'fa-solid fa-truck'],
                                    ['id' => 'plantel.lancamentos.pesagens',       'titulo' => 'Pesagens e eventos diversos',         'icone' => 'fa-solid fa-scale-balanced'],
                                ],
                            ],
                            ['id' => 'plantel.ver',           'titulo' => 'Visualizar (Visão Geral, Acompanhamento, Análise, Relatórios)', 'icone' => 'fa-solid fa-eye'],
                        ],
                    ],
                    [
                        'id' => 'gestacao',
                        'titulo' => 'Gestação / Cobertura',
                        'icone' => 'fa-solid fa-heart-pulse',
                        'children' => [
                            ['id' => 'gestacao.ver',       'titulo' => 'Visualizar gestação e retornos',       'icone' => 'fa-solid fa-eye'],
                            ['id' => 'gestacao.cio',       'titulo' => 'Registrar cio (escrita)',              'icone' => 'fa-solid fa-venus'],
                            ['id' => 'gestacao.cobertura', 'titulo' => 'Registrar cobertura/monta (escrita)',  'icone' => 'fa-solid fa-arrows-spin'],
                            ['id' => 'gestacao.diagnostico','titulo' => 'Diagnóstico / retorno cio (escrita)', 'icone' => 'fa-solid fa-stethoscope'],
                        ],
                    ],
                    [
                        'id' => 'maternidade',
                        'titulo' => 'Maternidade',
                        'icone' => 'fa-solid fa-baby',
                        'children' => [
                            ['id' => 'maternidade.ver',        'titulo' => 'Visualizar leitegadas e partos',     'icone' => 'fa-solid fa-eye'],
                            ['id' => 'maternidade.transferir', 'titulo' => 'Transferir fêmea para maternidade',  'icone' => 'fa-solid fa-right-to-bracket'],
                            ['id' => 'maternidade.parto',      'titulo' => 'Registrar parto (escrita)',          'icone' => 'fa-solid fa-person-breastfeeding'],
                            ['id' => 'maternidade.morte',      'titulo' => 'Registrar morte de leitão',          'icone' => 'fa-solid fa-skull'],
                            ['id' => 'maternidade.desmame',    'titulo' => 'Registrar desmame (escrita)',        'icone' => 'fa-solid fa-children'],
                        ],
                    ],
                    [
                        'id' => 'producao',
                        'titulo' => 'Produção (Creche / Terminação)',
                        'icone' => 'fa-solid fa-industry',
                        'children' => [
                            ['id' => 'producao.ver',      'titulo' => 'Visualizar lotes e pesagens',          'icone' => 'fa-solid fa-eye'],
                            ['id' => 'producao.lotes',    'titulo' => 'Criar e editar lotes (escrita)',       'icone' => 'fa-solid fa-boxes-stacked'],
                            ['id' => 'producao.pesagens', 'titulo' => 'Pesagens (escrita)',                   'icone' => 'fa-solid fa-scale-balanced'],
                            ['id' => 'producao.saidas',   'titulo' => 'Saídas / vendas de lote (escrita)',    'icone' => 'fa-solid fa-truck'],
                        ],
                    ],
                ],
            ],
            [
                'id' => 'analises',
                'titulo' => 'Análises e Relatórios',
                'icone' => 'fa-solid fa-chart-column',
                'children' => [
                    ['id' => 'analises.ver',                'titulo' => 'Acessar aba Análise (KPIs, gráficos)', 'icone' => 'fa-solid fa-chart-line'],
                    ['id' => 'analises.relatorios',         'titulo' => 'Acessar aba Relatórios',                 'icone' => 'fa-solid fa-file-invoice'],
                    ['id' => 'analises.relatorios.femeas',  'titulo' => 'Relatório de Fêmeas (PDF/CSV)',          'icone' => 'fa-solid fa-file-lines'],
                    ['id' => 'analises.relatorios.machos',  'titulo' => 'Relatório de Machos (PDF/CSV)',          'icone' => 'fa-solid fa-file-lines'],
                    ['id' => 'analises.formularios',        'titulo' => 'Formulários (ex: Cio de Leitoa PDF)',   'icone' => 'fa-solid fa-file-pdf'],
                    ['id' => 'analises.exportar',           'titulo' => 'Exportar relatórios PDF/CSV',           'icone' => 'fa-solid fa-file-export'],
                ],
            ],
            [
                'id' => 'sistema',
                'titulo' => 'Sistema (Administração)',
                'icone' => 'fa-solid fa-gears',
                'children' => [
                    ['id' => 'sistema.usuarios',            'titulo' => 'Usuários (cadastrar, editar, excluir, controle de acesso) — SÓ ADMINISTRADOR', 'icone' => 'fa-solid fa-users-gear'],
                    ['id' => 'sistema.metas',               'titulo' => 'Metas e Critérios — SÓ ADMINISTRADOR',   'icone' => 'fa-solid fa-sliders'],
                    ['id' => 'sistema.cadastros',           'titulo' => 'Cadastros auxiliares (causa, granja, etc)', 'icone' => 'fa-solid fa-list-check'],
                    ['id' => 'sistema.ajustes_gerais',      'titulo' => 'Ajustes gerais do sistema',               'icone' => 'fa-solid fa-screwdriver-wrench'],
                ],
            ],
        ];
    }

    /**
     * Retorna todas as chaves folha (ids terminais) da árvore — usada para normalizar.
     */
    public static function todasChavesPermissoes(): array
    {
        $arvore = self::arvorePermissoes();
        $folhas = [];
        $visit = function (array $nodes) use (&$visit, &$folhas): void {
            foreach ($nodes as $n) {
                if (empty($n['children'])) {
                    $folhas[] = $n['id'];
                } else {
                    $visit($n['children']);
                }
            }
        };
        $visit($arvore);
        return array_values(array_unique($folhas));
    }

    /**
     * Lógica central: verifica se o usuário pode executar a ação.
     *
     * @param  User|null  $user  Nulo usa Auth::user()
     * @param  string     $chave Chave da permissão (ex: 'plantel.lancamentos.femeas'). Aceita wildcard no final: 'plantel.*'
     * @param  bool       $escrita   True = ação de incluir/alterar/excluir; False = só visualização
     */
    public function pode(?User $user, string $chave, bool $escrita = false): bool
    {
        $user = $user ?? (Auth::check() ? Auth::user() : null);
        if (! $user instanceof User) {
            return false;
        }

        $perfil = (string) ($user->perfil ?? 'operador');

        // 1) ADMINISTRADOR = tudo permitido, NUNCA é bloqueado
        if ($perfil === self::PERFIL_ADMIN) {
            return true;
        }

        // 2) Se é um usuário NÃO admin tentando mexer em SISTEMA (usuários/metas) — bloqueia sempre
        if (str_starts_with($chave, 'sistema.usuarios') || str_starts_with($chave, 'sistema.metas')) {
            return false;
        }

        // 3) Resolve chaves wildcard no check (ex: usuario grava 'plantel.*' e perguntam 'plantel.lancamentos.femeas')
        $lista = static::listaPermissoesDoUsuario($user);

        $match = static::listaContem($lista, $chave);

        if ($perfil === self::PERFIL_OPERADOR) {
            // OPERADOR: DEFAULT LIBERADO. Lista = DENY list (marcado = BLOQUEADO)
            // Se a chave está NA lista -> bloqueado. Senão, permitido.
            if ($match) {
                return false;
            }
            return true;
        }

        // LEITOR: DEFAULT BLOQUEADO para escrita. Lista = ALLOW list (marcado = PERMITIDO)
        if (! $escrita) {
            // Ação de só leitura: para LEITOR sempre pode VER (exceto sistema.usuarios / sistema.metas bloqueados acima)
            // Mas se a lista contiver explicitamente negações (ex: planta.ver marcado NAO), isso deve bloquear.
            // Como nossa lista é ALLOW, se ela estiver VAZIA -> liberação leitura geral.
            if (empty($lista)) {
                return true;
            }
            // Lista NÃO-vazia: só permite se a chave está na lista OU é irmão de escrita e plantel.ver etc está permitido
            // Vamos ser permissivos para leitura se NÃO houver match de NÃO permitido
            $chaveLeitura = static::chavePaiDeLeitura($chave);
            if ($match) return true;
            if ($chaveLeitura && static::listaContem($lista, $chaveLeitura)) return true;
            return true;
        }

        // Escrita no LEITOR: SÓ permitido se CHAVE está na lista (allow list)
        return $match;
    }

    /**
     * Versão helper em classe.
     */
    public static function check(?User $user, string $chave, bool $escrita = false): bool
    {
        return app(static::class)->pode($user, $chave, $escrita);
    }

    /**
     * Extrai as chaves do usuário a partir do JSON.
     *
     * @return array<string>
     */
    public static function listaPermissoesDoUsuario(User $user): array
    {
        $col = $user->permissoes ?? null;
        if (is_string($col) && $col !== '') {
            try {
                $dec = json_decode($col, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($dec)) $col = $dec;
            } catch (\Throwable) {}
        }
        if (! is_array($col)) return [];
        return array_values(array_filter(array_map('strval', $col), static fn($s) => $s !== ''));
    }

    /**
     * Verifica se uma lista de permissões (com wildcards) dá match numa chave solicitada.
     *
     * @param  array<string>  $lista
     */
    public static function listaContem(array $lista, string $chave): bool
    {
        if ($lista === []) return false;
        $map = array_flip($lista);
        // Match exato primeiro
        if (isset($map[$chave])) return true;
        // Match wildcards ex: 'plantel.*'  cobre 'plantel.lancamentos.femeas'
        // Também match pai direto: se plantel.lancamentos tá na lista, filhos devem ser cobertos
        $parts = explode('.', $chave);
        $acc = '';
        foreach ($parts as $i => $p) {
            $acc = ($i === 0) ? $p : $acc.'.'.$p;
            if (isset($map[$acc])) return true;
            if (isset($map[$acc.'.*'])) return true;
        }
        // Depois wildcards diretos no array
        foreach ($lista as $pattern) {
            if (! str_contains($pattern, '*')) continue;
            $regex = '#^'.preg_quote($pattern, '#').'$#';
            $regex = str_replace('\\*', '.*', $regex);
            if (preg_match($regex, $chave)) return true;
        }
        return false;
    }

    protected static function chavePaiDeLeitura(string $chave): ?string
    {
        $parts = explode('.', $chave);
        if (count($parts) === 0) return null;
        return $parts[0].'.ver';
    }
}
