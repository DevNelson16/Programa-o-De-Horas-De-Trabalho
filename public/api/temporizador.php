<?php
/**
 * ============================================================
 * API do Temporizador
 * Endpoints: iniciar, pausar, parar, status
 * ============================================================
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar autenticacao
protegerPagina();

header('Content-Type: application/json');

$acao = $_GET['acao'] ?? '';
$user_id = $_SESSION['user_id'];
$resposta = ['sucesso' => false, 'mensagem' => 'Acao invalida'];

switch ($acao) {
    
    // Iniciar novo temporizador
    case 'iniciar':
        $dados = json_decode(file_get_contents('php://input'), true);
        $descricao = $dados['descricao'] ?? '';
        
        // Verificar se ja existe um ativo
        $ativo = fetchOne("SELECT id FROM horas WHERE user_id = ? AND status IN ('em_andamento', 'pausado')", [$user_id]);
        if ($ativo) {
            $resposta = ['sucesso' => false, 'mensagem' => 'Ja existe um temporizador ativo. Pare-o antes de iniciar um novo.'];
            break;
        }
        
        $hora_atual = date('H:i:s');
        $data_atual = date('Y-m-d');
        
        $registo_id = insert('horas', [
            'user_id'     => $user_id,
            'data'        => $data_atual,
            'hora_inicio' => $hora_atual,
            'status'      => 'em_andamento',
            'descricao'   => $descricao
        ]);
        
        registrarLog('temporizador_iniciar', 'horas', $registo_id);
        
        $resposta = [
            'sucesso'     => true,
            'registo_id'  => $registo_id,
            'hora_inicio' => $hora_atual,
            'mensagem'    => 'Temporizador iniciado'
        ];
        break;
    
    // Pausar temporizador
    case 'pausar':
        $dados = json_decode(file_get_contents('php://input'), true);
        $registo_id = $dados['registo_id'] ?? 0;
        
        $registo = fetchOne("SELECT * FROM horas WHERE id = ? AND user_id = ?", [$registo_id, $user_id]);
        if (!$registo) {
            $resposta = ['sucesso' => false, 'mensagem' => 'Registo nao encontrado'];
            break;
        }
        
        // Calcular horas ate agora
        $inicio = strtotime($registo['data'] . ' ' . $registo['hora_inicio']);
        $agora = time();
        $total_segundos = $agora - $inicio;
        $total_horas = round($total_segundos / 3600, 2);
        
        update('horas', [
            'status'      => 'pausado',
            'total_horas' => $total_horas
        ], 'id = :id', ['id' => $registo_id]);
        
        registrarLog('temporizador_pausar', 'horas', $registo_id);
        
        $resposta = ['sucesso' => true, 'total_horas' => $total_horas, 'mensagem' => 'Temporizador pausado'];
        break;
    
    // Parar e guardar temporizador
    case 'parar':
        $dados = json_decode(file_get_contents('php://input'), true);
        $registo_id = $dados['registo_id'] ?? 0;
        $descricao = $dados['descricao'] ?? '';
        
        $registo = fetchOne("SELECT * FROM horas WHERE id = ? AND user_id = ?", [$registo_id, $user_id]);
        if (!$registo) {
            $resposta = ['sucesso' => false, 'mensagem' => 'Registo nao encontrado'];
            break;
        }
        
        // Calcular horas finais
        $inicio = strtotime($registo['data'] . ' ' . $registo['hora_inicio']);
        $agora = time();
        $total_segundos = $agora - $inicio;
        $total_horas = round($total_segundos / 3600, 2);
        
        $hora_fim = date('H:i:s');
        
        update('horas', [
            'hora_fim'    => $hora_fim,
            'total_horas' => $total_horas,
            'status'      => 'concluido',
            'descricao'   => $descricao ?: $registo['descricao']
        ], 'id = :id', ['id' => $registo_id]);
        
        registrarLog('temporizador_parar', 'horas', $registo_id, "Total: {$total_horas}h");
        
        $resposta = [
            'sucesso'     => true,
            'total_horas' => $total_horas,
            'hora_fim'    => $hora_fim,
            'mensagem'    => 'Registo guardado com sucesso'
        ];
        break;
    
    // Verificar status do temporizador ativo
    case 'status':
        $registo = fetchOne("SELECT * FROM horas WHERE user_id = ? AND status IN ('em_andamento', 'pausado') ORDER BY id DESC LIMIT 1", [$user_id]);
        
        $resposta = [
            'ativo'   => !!$registo,
            'registo' => $registo
        ];
        break;
    
    default:
        $resposta = ['sucesso' => false, 'mensagem' => 'Acao nao reconhecida'];
}

echo json_encode($resposta);
