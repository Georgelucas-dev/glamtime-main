<?php
require_once __DIR__ . '/conexao.php';

require_once __DIR__ . '/auth_check.php';
$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT cliente_id, servico_id FROM agendamentos WHERE id = :id AND status = 'agendado'");
$stmt->execute([':id' => $id]);
$ag = $stmt->fetch();

$csrf = $_SESSION['csrf'] ?? ($_SESSION['csrf'] = bin2hex(random_bytes(32)));
if (!$ag) { header('Location: agendamentos.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['csrf'] ?? '') !== $csrf) { die('Token inválido.'); }
    $novaData = is_string($_POST['data_hora'] ?? null) ? $_POST['data_hora'] : '';
    require_once __DIR__ . '/src/AgendamentoDAO.php';
    $dao = new AgendamentoDAO($pdo);

    $pdo->beginTransaction();
    try {
        $dao->bloquearAgenda();
        [$novaData, $fim] = $dao->validarDados((int) $ag['cliente_id'], (int) $ag['servico_id'], $novaData);
        if ($dao->verificarConflito($novaData, $fim)) {
            throw new RuntimeException('Horário conflita com outro agendamento.');
        }
        $ins = $pdo->prepare("INSERT INTO agendamentos (cliente_id, servico_id, data_hora, status)
                              VALUES (:c, :s, :d, 'agendado')");
        $ins->execute([':c' => (int) $ag['cliente_id'], ':s' => (int) $ag['servico_id'], ':d' => $novaData]);
        $upd = $pdo->prepare("UPDATE agendamentos SET status = 'concluido' WHERE id = :id AND status = 'agendado'");
        $upd->execute([':id' => $id]);
        if (!$upd->rowCount()) {
            throw new RuntimeException('O agendamento anterior já foi processado.');
        }
        $pdo->commit();
        $_SESSION['flash'] = 'Novo agendamento criado a partir do anterior.';
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log($e->getMessage());
        $_SESSION['flash'] = $e instanceof PDOException ? 'Não foi possível reagendar.' : $e->getMessage();
        $_SESSION['flash_tipo'] = 'danger';
    }
    header('Location: agendamentos.php'); exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>GlamTime — Agendar novamente</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <?php require_once __DIR__ . '/navbar.php'; ?>
  <main class="container" style="max-width:560px">
    <div class="card shadow-sm"><div class="card-body">
      <h5 class="fw-bold mb-3">Agendar novamente #<?= $id ?></h5>
      <p>O cliente e o serviço serão mantidos. Ao confirmar, o anterior será marcado como concluído.</p>
      <form method="post" onsubmit="return confirm('Criar novo agendamento e concluir o anterior?')">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
        <div class="mb-3">
          <label class="form-label">Nova data e hora</label>
          <input type="datetime-local" class="form-control" name="data_hora" required>
        </div>
        <button class="btn btn-primary w-100">Agendar novamente</button>
        <a href="agendamentos.php" class="btn btn-link w-100">Voltar</a>
      </form>
    </div></div>
  </main>
</body>
</html>
