<?php
/**
 * ============================================================
 * Pagina de Relatorios
 * Enviar e Visualizar Relatorios de Estudo
 * ============================================================
 */

require_once __DIR__ . '/../includes/header.php';

$user_id = $_SESSION['user_id'];
$titulo = 'Relatorios';

// Processar envio de relatorio
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'enviar') {
    $conteudo = trim($_POST['conteudo'] ?? '');
    $data = $_POST['data'] ?? date('Y-m-d');
    $tags = trim($_POST['tags'] ?? '');
    
    if (empty($conteudo)) {
        setFlash('error', 'O conteudo do relatorio e obrigatorio.');
    } else {
        insert('relatorios', [
            'user_id'  => $user_id,
            'data'     => $data,
            'conteudo' => $conteudo,
            'tags'     => $tags
        ]);
        setFlash('success', 'Relatorio enviado com sucesso!');
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Filtros
$filtroTags = $_GET['tag'] ?? '';
$busca = $_GET['busca'] ?? '';

$params = [$user_id];
$where = "r.user_id = ?";

if ($filtroTags) {
    $where .= " AND r.tags LIKE ?";
    $params[] = "%{$filtroTags}%";
}
if ($busca) {
    $where .= " AND (r.conteudo LIKE ? OR r.tags LIKE ?)";
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
}

// Paginacao
$porPagina = 10;
$pagina = max(1, (int)($_GET['pagina'] ?? 1));
$offset = ($pagina - 1) * $porPagina;

$totalRegistos = fetchOne("SELECT COUNT(*) as total FROM relatorios r WHERE {$where}", $params);
$totalPaginas = ceil($totalRegistos['total'] / $porPagina);

$params[] = $offset;
$params[] = $porPagina;

$relatorios = fetchAll("SELECT r.*, u.nome as autor FROM relatorios r JOIN utilizadores u ON r.user_id = u.id WHERE {$where} ORDER BY r.data DESC, r.created_at DESC LIMIT ? OFFSET ?", $params);

// Tags unicas para filtro
$todasTags = fetchAll("SELECT DISTINCT tags FROM relatorios WHERE user_id = ? AND tags IS NOT NULL AND tags != ''", [$user_id]);
$tagsLista = [];
foreach ($todasTags as $t) {
    foreach (explode(',', $t['tags']) as $tag) {
        $tag = trim($tag);
        if ($tag && !in_array($tag, $tagsLista)) {
            $tagsLista[] = $tag;
        }
    }
}
sort($tagsLista);
?>

<div class="page-header">
    <h1><i class="fas fa-file-lines" style="margin-right:12px;color:var(--primary);"></i>Relatorios de Estudo</h1>
    <p>Envia relatórios do que estudaste e consulta o teu historico.</p>
</div>

<!-- Formulario de Envio -->
<div class="card fade-in">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-pen-to-square" style="margin-right:8px;color:var(--primary);"></i>Novo Relatorio</h2>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <input type="hidden" name="acao" value="enviar">
            
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label" for="data">Data <span>*</span></label>
                    <input type="date" id="data" name="data" class="form-input" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="tags">Tags (separadas por virgula)</label>
                    <input type="text" id="tags" name="tags" class="form-input" placeholder="PHP, JavaScript, CSS...">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="conteudo">O que estudaste? <span>*</span></label>
                <textarea id="conteudo" name="conteudo" class="form-input" rows="6" placeholder="Descreve o que aprendeste, exercicios que fizeste, duvidas que surgiram, proximos passos..." required></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-paper-plane"></i> Enviar Relatorio
            </button>
        </form>
    </div>
</div>

<!-- Lista de Relatorios -->
<div style="margin-top:32px;">
    <div class="toolbar">
        <h2 style="font-size:var(--font-size-xl);font-weight:700;">Meus Relatorios</h2>
        <div class="toolbar-filters">
            <form method="GET" class="toolbar-filters" style="margin:0;">
                <?php if (!empty($tagsLista)): ?>
                <select name="tag" class="form-input" onchange="this.form.submit()" style="width:auto;">
                    <option value="">Todas as tags</option>
                    <?php foreach ($tagsLista as $tag): ?>
                    <option value="<?php echo htmlspecialchars($tag); ?>" <?php echo $filtroTags === $tag ? 'selected' : ''; ?>><?php echo htmlspecialchars($tag); ?></option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" name="busca" class="form-input" placeholder="Pesquisar..." value="<?php echo htmlspecialchars($busca); ?>">
                </div>
                <?php if ($filtroTags || $busca): ?>
                <a href="relatorios.php" class="btn btn-sm btn-secondary"><i class="fas fa-times"></i> Limpar</a>
                <?php endif; ?>
            </form>
        </div>
    </div>
    
    <?php if (empty($relatorios)): ?>
    <div class="card">
        <div class="empty-state">
            <i class="fas fa-file-lines"></i>
            <h3>Sem relatorios ainda</h3>
            <p>Envia o teu primeiro relatorio de estudo acima.</p>
        </div>
    </div>
    <?php else: ?>
    <div style="display:grid; gap:16px;">
        <?php foreach ($relatorios as $rel): ?>
        <div class="relatorio-card fade-in">
            <div class="relatorio-header">
                <div class="relatorio-autor">
                    <div class="relatorio-avatar"><?php echo strtoupper(substr($rel['autor'], 0, 2)); ?></div>
                    <div class="relatorio-meta">
                        <h4><?php echo htmlspecialchars($rel['autor']); ?></h4>
                        <span><?php echo formatarData($rel['data']); ?> &bull; <?php echo diaSemana($rel['data']); ?></span>
                    </div>
                </div>
            </div>
            <div class="relatorio-conteudo"><?php echo nl2br(htmlspecialchars($rel['conteudo'])); ?></div>
            <?php if ($rel['tags']): ?>
            <div class="relatorio-tags">
                <?php foreach (explode(',', $rel['tags']) as $tag): ?>
                <span class="tag"><?php echo htmlspecialchars(trim($tag)); ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Paginacao -->
    <?php if ($totalPaginas > 1): ?>
    <div class="pagination">
        <?php if ($pagina > 1): ?>
        <a href="?pagina=<?php echo $pagina - 1; ?>&tag=<?php echo urlencode($filtroTags); ?>&busca=<?php echo urlencode($busca); ?>" class="page-link"><i class="fas fa-chevron-left"></i></a>
        <?php endif; ?>
        <?php for ($i = max(1, $pagina - 2); $i <= min($totalPaginas, $pagina + 2); $i++): ?>
        <a href="?pagina=<?php echo $i; ?>&tag=<?php echo urlencode($filtroTags); ?>&busca=<?php echo urlencode($busca); ?>" class="page-link <?php echo $i === $pagina ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
        <?php if ($pagina < $totalPaginas): ?>
        <a href="?pagina=<?php echo $pagina + 1; ?>&tag=<?php echo urlencode($filtroTags); ?>&busca=<?php echo urlencode($busca); ?>" class="page-link"><i class="fas fa-chevron-right"></i></a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
