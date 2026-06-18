<?php
/**
 * ============================================================
 * Conexao a Base de Dados com PDO
 * Prepared Statements para seguranca (SQL Injection)
 * ============================================================
 */

require_once __DIR__ . '/config.php';

/**
 * Retorna a conexao PDO com a base de dados
 * @return PDO
 */
function getDB(): PDO {
    static $db = null;
    
    if ($db === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $opcoes = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false, // Prepared statements reais
            ];
            $db = new PDO($dsn, DB_USER, DB_PASS, $opcoes);
        } catch (PDOException $e) {
            die("Erro de conexao a base de dados: " . $e->getMessage());
        }
    }
    
    return $db;
}

/**
 * Executa uma query com prepared statement
 * @param string $sql Query SQL com placeholders (? ou :nome)
 * @param array $params Parametros para a query
 * @return PDOStatement
 */
function query(string $sql, array $params = []): PDOStatement {
    $db = getDB();
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Busca um unico registro
 * @param string $sql Query SQL
 * @param array $params Parametros
 * @return array|null
 */
function fetchOne(string $sql, array $params = []): ?array {
    $stmt = query($sql, $params);
    $result = $stmt->fetch();
    return $result ?: null;
}

/**
 * Busca varios registros
 * @param string $sql Query SQL
 * @param array $params Parametros
 * @return array
 */
function fetchAll(string $sql, array $params = []): array {
    $stmt = query($sql, $params);
    return $stmt->fetchAll();
}

/**
 * Insere dados e retorna o ID gerado
 * @param string $tabela Nome da tabela
 * @param array $dados Associativo [coluna => valor]
 * @return int ID do registo inserido
 */
function insert(string $tabela, array $dados): int {
    $db = getDB();
    $colunas = implode(', ', array_keys($dados));
    $placeholders = ':' . implode(', :', array_keys($dados));
    
    $sql = "INSERT INTO {$tabela} ({$colunas}) VALUES ({$placeholders})";
    $stmt = $db->prepare($sql);
    $stmt->execute($dados);
    
    return (int) $db->lastInsertId();
}

/**
 * Atualiza registos
 * @param string $tabela Nome da tabela
 * @param array $dados Associativo [coluna => valor]
 * @param string $where Clausula WHERE
 * @param array $whereParams Parametros do WHERE
 * @return int Numero de linhas afetadas
 */
function update(string $tabela, array $dados, string $where, array $whereParams = []): int {
    $db = getDB();
    $set = [];
    foreach (array_keys($dados) as $coluna) {
        $set[] = "{$coluna} = :{$coluna}";
    }
    $setStr = implode(', ', $set);
    
    $sql = "UPDATE {$tabela} SET {$setStr} WHERE {$where}";
    $stmt = $db->prepare($sql);
    $stmt->execute(array_merge($dados, $whereParams));
    
    return $stmt->rowCount();
}

/**
 * Apaga registos
 * @param string $tabela Nome da tabela
 * @param string $where Clausula WHERE
 * @param array $params Parametros
 * @return int Numero de linhas afetadas
 */
function delete(string $tabela, string $where, array $params = []): int {
    $sql = "DELETE FROM {$tabela} WHERE {$where}";
    $stmt = query($sql, $params);
    return $stmt->rowCount();
}
