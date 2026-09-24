<?php
require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['csrf']) || !is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
    header('Location: agendamentos.php'); exit;
}

$clienteId = (int) ($_POST['cliente_id'] ?? 0);
$servicoId = (int) ($_POST['servico_id'] ?? 0);
$dataHora  = is_string($_POST['data_hora'] ?? null) ? $_POST['data_hora'] : '';

require_once __DIR__ . '/src/AgendamentoDAO.php';
$dao = new AgendamentoDAO($pdo);
$pdo->beginTransaction();
try {
    $dao->bloquearAgenda();
    [$dataHora, $fim] = $dao->validarDados($clienteId, $servicoId, $dataHora);
    if ($dao->verificarConflito($dataHora, $fim)) {
        throw new RuntimeException('Horário conflita com outro agendamento ativo.');
    }

    $ins = $pdo->prepare("INSERT INTO agendamentos (cliente_id, servico_id, data_hora, status) VALUES (:c, :s, :d, 'agendado')");
    $ins->execute([':c' => $clienteId, ':s' => $servicoId, ':d' => $dataHora]);
    $pdo->commit();
    $_SESSION['flash'] = 'Agendamento criado com sucesso!';
} catch (Exception $e) {
    $pdo->rollBack();
    error_log($e->getMessage());
    $_SESSION['flash'] = $e instanceof PDOException ? 'Não foi possível salvar o agendamento.' : $e->getMessage();
    $_SESSION['flash_tipo'] = 'danger';
}
header('Location: agendamentos.php');
exit;