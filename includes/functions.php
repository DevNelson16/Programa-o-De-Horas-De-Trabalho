<?php
/**
 * ============================================================
 * Funcoes Auxiliares do Sistema
 * ============================================================
 */

require_once __DIR__ . '/db.php';

/**
 * Formata um numero de horas para exibicao (ex: 3.5 -> "3h 30min")
 * @param float $horas
 * @return string
 */
function formatarHoras(float $horas): string {
    $h = floor($horas);
    $m = round(($horas - $h) * 60);
    if ($m == 60) {
        $h++;
        $m = 0;
    }
    return "{$h}h " . ($m > 0 ? "{$m}min" : "");
}

/**
 * Formata uma data para o padrao portugues
 * @param string $data Data no formato Y-m-d
 * @return string
 */
function formatarData(string $data): string {
    $meses = [
        1 => 'Janeiro', 'Fevereiro', 'Marco', 'Abril', 'Maio', 'Junho',
        'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'
    ];
    $d = date('j', strtotime($data));
    $m = $meses[(int)date('n', strtotime($data))];
    $a = date('Y', strtotime($data));
    return "{$d} de {$m} de {$a}";
}

/**
 * Obtem o nome do dia da semana
 * @param string $data
 * @return string
 */
function diaSemana(string $data): string {
    $dias = ['Domingo', 'Segunda-feira', 'Terca-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sabado'];
    return $dias[date('w', strtotime($data))];
}

/**
 * Obtem as datas da semana atual (segunda a domingo)
 * @return array ['inicio' => 'Y-m-d', 'fim' => 'Y-m-d']
 */
function getSemanaAtual(): array {
    $hoje = date('Y-m-d');
    $diaSemana = date('N', strtotime($hoje)); // 1=Segunda, 7=Domingo
    $inicio = date('Y-m-d', strtotime($hoje . ' -' . ($diaSemana - 1) . ' days'));
    $fim = date('Y-m-d', strtotime($inicio . ' +6 days'));
    return ['inicio' => $inicio, 'fim' => $fim];
}

/**
 * Obtem as datas do mes atual
 * @return array ['inicio' => 'Y-m-d', 'fim' => 'Y-m-d']
 */
function getMesAtual(): array {
    $inicio = date('Y-m-01');
    $fim = date('Y-m-t');
    return ['inicio' => $inicio, 'fim' => $fim];
}

/**
 * Estatisticas de horas por utilizador
 * @param int $user_id
 * @return array
 */
function getEstatisticasHoras(int $user_id): array {
    $semana = getSemanaAtual();
    $mes = getMesAtual();
    
    // Total de horas hoje
    $hoje = fetchOne("SELECT COALESCE(SUM(total_horas), 0) as total FROM horas WHERE user_id = ? AND data = CURDATE() AND status = 'concluido'", [$user_id]);
    
    // Total de horas esta semana
    $semanaTotal = fetchOne("SELECT COALESCE(SUM(total_horas), 0) as total FROM horas WHERE user_id = ? AND data BETWEEN ? AND ? AND status = 'concluido'", [$user_id, $semana['inicio'], $semana['fim']]);
    
    // Total de horas este mes
    $mesTotal = fetchOne("SELECT COALESCE(SUM(total_horas), 0) as total FROM horas WHERE user_id = ? AND data BETWEEN ? AND ? AND status = 'concluido'", [$user_id, $mes['inicio'], $mes['fim']]);
    
    // Total de horas sempre
    $total = fetchOne("SELECT COALESCE(SUM(total_horas), 0) as total FROM horas WHERE user_id = ? AND status = 'concluido'", [$user_id]);
    
    // Media diaria (baseada nos dias com registos)
    $media = fetchOne("SELECT COALESCE(AVG(total_horas), 0) as media FROM (SELECT SUM(total_horas) as total_horas FROM horas WHERE user_id = ? AND status = 'concluido' GROUP BY data) as medias", [$user_id]);
    
    return [
        'hoje'   => (float) $hoje['total'],
        'semana' => (float) $semanaTotal['total'],
        'mes'    => (float) $mesTotal['total'],
        'total'  => (float) $total['total'],
        'media'  => round((float) $media['media'], 2)
    ];
}

/**
 * Estatisticas globais (para admin)
 * @return array
 */
function getEstatisticasGlobais(): array {
    $semana = getSemanaAtual();
    $mes = getMesAtual();
    
    // Total de horas esta semana (todos)
    $sem = fetchOne("SELECT COALESCE(SUM(total_horas), 0) as total FROM horas WHERE data BETWEEN ? AND ? AND status = 'concluido'", [$semana['inicio'], $semana['fim']]);
    
    // Total de horas este mes (todos)
    $mesT = fetchOne("SELECT COALESCE(SUM(total_horas), 0) as total FROM horas WHERE data BETWEEN ? AND ? AND status = 'concluido'", [$mes['inicio'], $mes['fim']]);
    
    // Media diaria global
    $media = fetchOne("SELECT COALESCE(AVG(total_horas), 0) as media FROM (SELECT SUM(total_horas) as total_horas FROM horas WHERE status = 'concluido' GROUP BY data) as medias");
    
    // Total de relatorios este mes
    $relMes = fetchOne("SELECT COUNT(*) as total FROM relatorios WHERE data BETWEEN ? AND ?", [$mes['inicio'], $mes['fim']]);
    
    // Total de utilizadores
    $users = fetchOne("SELECT COUNT(*) as total FROM utilizadores WHERE ativo = 1");
    
    // Horas por utilizador (top 10)
    $porUser = fetchAll("SELECT u.nome, COALESCE(SUM(h.total_horas), 0) as total FROM utilizadores u LEFT JOIN horas h ON u.id = h.user_id AND h.status = 'concluido' AND h.data BETWEEN ? AND ? WHERE u.ativo = 1 GROUP BY u.id ORDER BY total DESC LIMIT 10", [$mes['inicio'], $mes['fim']]);
    
    return [
        'semana'      => (float) $sem['total'],
        'mes'         => (float) $mesT['total'],
        'media'       => round((float) $media['media'], 2),
        'relatorios'  => (int) $relMes['total'],
        'utilizadores'=> (int) $users['total'],
        'porUser'     => $porUser
    ];
}

/**
 * Regista uma acao no log
 * @param string $acao
 * @param string|null $tabela
 * @param int|null $registro_id
 * @param string|null $detalhes
 */
function registrarLog(string $acao, ?string $tabela = null, ?int $registro_id = null, ?string $detalhes = null): void {
    $user_id = $_SESSION['user_id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    
    query("INSERT INTO logs (user_id, acao, tabela_afetada, registro_id, detalhes, ip_address) VALUES (?, ?, ?, ?, ?, ?)", 
        [$user_id, $acao, $tabela, $registro_id, $detalhes, $ip]);
}

/**
 * Flash messages - mensagens temporarias
 * @param string $tipo 'success' | 'error' | 'warning' | 'info'
 * @param string $mensagem
 */
function setFlash(string $tipo, string $mensagem): void {
    $_SESSION['flash'] = ['tipo' => $tipo, 'mensagem' => $mensagem];
}

/**
 * Obtem e limpa a flash message
 * @return array|null
 */
function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
