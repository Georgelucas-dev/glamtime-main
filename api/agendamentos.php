<?php
require_once __DIR__ . '/../conexao.php';

header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['erro' => 'Acesso restrito. Faça login.']);
    exit;
}
$stmt = $pdo->query("SELECT a.id, c.nome cliente, s.nome servico, a.data_hora, a.status
                     FROM agendamentos a JOIN clientes c ON a.cliente_id=c.id JOIN servicos s ON a.servico_id=s.id
                     ORDER BY a.data_hora");
echo json_encode($stmt->fetchAll());
exit;