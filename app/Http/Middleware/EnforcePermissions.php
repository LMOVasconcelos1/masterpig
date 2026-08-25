<?php

namespace App\Http\Middleware;

use App\Services\PermissaoService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware GLOBAL de enforcement do RBAC.
 *
 * PROBLEMA RESOLVIDO:
 *   Antes, as permissões eram SALVAS no banco pela tela Controle de Acesso,
 *   mas NUNCA eram VALIDADAS em nenhum ponto das rotas/controllers.
 *   Este middleware intercepta TODAS as requisições autenticadas no pipeline
 *   e decide se bloqueia (abort 403) ou não com base na lógica do PermissaoService.
 *
 * ORDEM CRÍTICA NO PIPELINE (bootstrap/app.php):
 *   EnsureLocalSessionCookie
 *       → (autenticação web)
 *       → EnsureTenantSelected  (define conexão tenant + sessão tenant_db)
 *       → ApplyUserSchema       (garante colunas perfil/permissoes na tabela usuario)
 *       → InjectDashboardNotifications
 *       → **EnforcePermissions** (este, ÚLTIMO, pois precisa de tudo acima pronto)
 *
 * ESTRATÉGIA DE MAPEAMENTO (URL → chave permissão):
 *   1. Temos um array de REGRAS [regex_path => chave_pai].
 *   2. Método GET/HEAD = ação de LEITURA → perm(chave_pai.ver, false).
 *   3. Método POST/PATCH/PUT/DELETE = ESCRITA → perm(chave_pai.*, true).
 *   4. Nenhuma regra bateu → fallback PERMISSIVO (libera) para não quebrar telas novas.
 *   5. Admin (perfil=administrador) já é liberado DIRETAMENTE no PermissaoService::pode(),
 *      então este middleware não precisa de if especial.
 */
class EnforcePermissions
{
    /**
     * Tabela de mapeamento: Expressão regular (URL PATH sem query string) => chave-pai.
     *
     * A chave-pai é concatenada com ".ver" para GET e com ".*" para escrita.
     * Sempre use paths absolutos (começam com /).
     *
     * @var array<string, string>
     */
    private const REGRAS_MAP = [
        // ============== MANEJOS: PLANTEL REPRODUTIVO ==============
        '#^/plantel/analises#i'                         => 'analises',
        '#^/plantel/femeas/compras#i'                   => 'plantel.lancamentos.compras',
        '#^/plantel/femeas/mortes#i'                    => 'plantel.lancamentos',
        '#^/plantel/femeas/descarte#i'                  => 'plantel.lancamentos',
        '#^/plantel/femeas/venda#i'                     => 'plantel.lancamentos',
        '#^/plantel/femeas/movimentos#i'                => 'plantel.lancamentos',
        '#^/plantel/femeas#i'                           => 'plantel',
        '#^/plantel/machos/compras#i'                   => 'plantel.lancamentos.compras',
        '#^/plantel/machos/mortes#i'                    => 'plantel.lancamentos',
        '#^/plantel/machos/descarte#i'                  => 'plantel.lancamentos',
        '#^/plantel/machos/venda#i'                     => 'plantel.lancamentos',
        '#^/plantel/machos#i'                           => 'plantel',
        '#^/plantel#i'                                  => 'plantel',

        // ============== MANEJOS: GESTAÇÃO / COBERTURA ==============
        '#^/gestacao#i'                                 => 'gestacao',

        // ============== MANEJOS: MATERNIDADE ==============
        '#^/maternidade#i'                              => 'maternidade',

        // ============== MANEJOS: PRODUÇÃO (CRECHE / TERMINAÇÃO) ==============
        '#^/creche#i'                                   => 'producao',
        '#^/terminacao#i'                               => 'producao',

        // ============== RELATÓRIOS / ANÁLISES ==============
        '#^/relatorios/plantel#i'                       => 'analises.relatorios',

        // ============== SISTEMA / ADMINISTRAÇÃO ==============
        // (usuarios e metas já são bloqueados pelo middleware 'admin' + hardcode no PermissaoService)
        '#^/admin/usuarios#i'                           => 'sistema.usuarios',
        '#^/admin/metas#i'                              => 'sistema.metas',
        '#^/admin/criterios#i'                          => 'sistema.metas',
        '#^/admin/zerar#i'                              => 'sistema.ajustes_gerais',
        '#^/admin/causas#i'                             => 'sistema.cadastros',
        '#^/admin/grupo-causa#i'                        => 'sistema.cadastros',
        '#^/admin/racoes#i'                             => 'sistema.cadastros',
        '#^/admin/tipos-racao#i'                        => 'sistema.cadastros',
        '#^/admin/racas#i'                              => 'sistema.cadastros',
        '#^/admin/fornecedores#i'                       => 'sistema.cadastros',
        '#^/admin/clientes#i'                           => 'sistema.cadastros',
        '#^/admin/semen#i'                              => 'sistema.cadastros',
        '#^/admin/alteracoes#i'                         => 'sistema.ajustes_gerais',
        '#^/admin#i'                                    => 'sistema.ajustes_gerais',

        // ============== APIs ==============
        '#^/api/plantel/femeas/cios#i'                  => 'plantel.lancamentos',
        '#^/api/plantel/femeas#i'                       => 'plantel',
        '#^/api/plantel/machos#i'                       => 'plantel',
        '#^/api/plantel/causas#i'                       => 'plantel.lancamentos',
        '#^/api/gestacao/coberturas#i'                  => 'gestacao.cobertura',
        '#^/api/gestacao/cio#i'                         => 'gestacao.cio',
        '#^/api/gestacao/perdas#i'                      => 'gestacao.diagnostico',
        '#^/api/gestacao/salta-cio#i'                   => 'gestacao.diagnostico',
        '#^/api/gestacao/metas#i'                       => 'gestacao',
        '#^/api/gestacao#i'                             => 'gestacao',
        '#^/api/terminacao#i'                           => 'producao',
        '#^/api/creche#i'                               => 'producao',
        '#^/api/semen#i'                                => 'sistema.cadastros',
        '#^/api/metas#i'                                => 'sistema.metas',
        '#^/api/criterios#i'                            => 'sistema.metas',
        '#^/api/utilitarios#i'                          => 'sistema.ajustes_gerais',
        '#^/api/grupos-causa#i'                         => 'sistema.cadastros',
        '#^/api/fornecedores#i'                         => 'sistema.cadastros',
        '#^/api/tipos-racao#i'                          => 'sistema.cadastros',
        '#^/api/racas#i'                                => 'sistema.cadastros',
    ];

    /**
     * Paths que são LIBERADOS SEM CHECAGEM (fallback seguro, nunca bloqueia páginas essenciais).
     *
     * @var array<string>
     */
    private const WHITELIST_EXATOS = [
        '/',
        '/dashboard',
        '/login',
        '/logout',
        '/up',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        try {
            $path = '/'.ltrim($request->decodedPath(), '/');
            $method = strtoupper($request->method());

            // 1) Whitelist: páginas essenciais pulam checagem.
            if (in_array($path, self::WHITELIST_EXATOS, true)) {
                return $next($request);
            }

            // 2) URLs de assets / build / storage / profile photo: nunca bloqueia.
            if (str_starts_with($path, '/build/')
                || str_starts_with($path, '/vendor/')
                || str_starts_with($path, '/storage/')
                || str_starts_with($path, '/profile/')
                || str_starts_with($path, '/favicon')
                || str_starts_with($path, '/robots.')
                || str_starts_with($path, '/manifest.')
                || str_starts_with($path, '/sw.')
                || $path === '/sw.js') {
                return $next($request);
            }

            // 3) Se o usuário NÃO está autenticado: deixa passar.
            //    O middleware 'auth' do Laravel vai redirecionar para /login.
            if (! Auth::check()) {
                return $next($request);
            }

            /** @var object|null $authUser */
            $authUser = Auth::user();
            if ($authUser === null) {
                return $next($request);
            }

            // 4) Administrador: PermissaoService::pode() já libera tudo.
            //    Mas vamos ser defensivos: se perfil === administrador, retorna direto.
            $perfil = (string) ($authUser->perfil ?? 'operador');
            if ($perfil === PermissaoService::PERFIL_ADMIN) {
                return $next($request);
            }

            // 5) Detecta se é ação de ESCRITA (POST/PATCH/PUT/DELETE) ou LEITURA.
            $escrita = in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true);

            // 6) Verifica se a URL bate em alguma regra.
            $chaveParaValidar = null;
            $chavePai = null;
            foreach (self::REGRAS_MAP as $regex => $pai) {
                if (preg_match($regex, $path)) {
                    $chavePai = $pai;
                    break;
                }
            }

            // Nenhuma regra bateu → fallback permissivo: NÃO BLOQUEIA.
            // Motivo: é melhor deixar passar páginas novas do que quebrar telas acidentalmente.
            if ($chavePai === null) {
                return $next($request);
            }

            // Regra de checagem:
            //   - Leitura (GET/HEAD): tenta "pai.ver" primeiro; se não bater, tenta "pai.*"
            //     (pois às vezes o usuário marcou o pai inteiro em allow list)
            //   - Escrita: sempre "pai.*" (qualquer filho) ou exata.
            if ($escrita) {
                $chaveParaValidar = $chavePai.'.*';
            } else {
                $chaveParaValidar = $chavePai.'.ver';
            }

            // 7) Finalmente: executa a checagem.
            $permitido = PermissaoService::check(null, $chaveParaValidar, $escrita);

            // Fallback para leitura: se "pai.ver" não estava permitido,
            //   mas o usuário tinha "pai.*" marcado, PermissaoService::listaContem
            //   cobre via wildcard pai → listaContem já considera pai como match de filhos.
            //   No PermissaoService, se "plantel" foi marcado, plantel.ver também cai em match por pai.
            //   Então não precisa de segunda checagem. Mas faremos 2ª tentativa com "pai.*" para leitura também:
            if (! $permitido && ! $escrita) {
                $permitido = PermissaoService::check(null, $chavePai.'.*', false);
            }
            // Última tentativa: a chave_pai SOZINHA (sem sufixo) foi marcada:
            if (! $permitido) {
                $permitido = PermissaoService::check(null, $chavePai, $escrita);
            }

            if (! $permitido) {
                $msgUsuario = 'Usuário não tem acesso nessa rotina.';

                if ($request->expectsJson() || str_starts_with($path, '/api/')) {
                    return response()->json([
                        'success' => false,
                        'message' => $msgUsuario,
                        'codigo'  => 403,
                        'perfil'  => $perfil,
                        'chave'   => $chaveParaValidar,
                        'escrita' => $escrita,
                    ], 403);
                }

                $redirect = redirect();
                if ($request->headers->has('Referer') && (string) $request->headers->get('Referer') !== '') {
                    $redirect = $redirect->back();
                } else {
                    $redirect = $redirect->to(route('dashboard', [], false));
                }

                return $redirect->with('error', $msgUsuario);
            }

            return $next($request);
        } catch (\Throwable $e) {
            // IMPORTANTE: NÃO DEIXE uma exceção no middleware RBAC quebrar a aplicação inteira.
            // Se houver erro (ex: banco indisponível temporariamente), grava log mas deixa passar.
            report($e);
            return $next($request);
        }
    }
}
