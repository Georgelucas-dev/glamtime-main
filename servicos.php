<?php
require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/auth_check.php';
if (($_SESSION['usuario_role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Somente administradores podem gerenciar serviços.');
}
$csrf = $_SESSION['csrf'] ?? ($_SESSION['csrf'] = bin2hex(random_bytes(32)));
$erro = null;
$servico = ['id' => 0, 'nome' => '', 'duracao_min' => '', 'preco' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_string($_POST['csrf'] ?? null) || !hash_equals($csrf, $_POST['csrf'])) {
        http_response_code(403); exit('Token CSRF inválido.');
    }
    $id = (int) ($_POST['id'] ?? 0);
    $acao = $_POST['acao'] ?? '';
    $nome = trim($_POST['nome'] ?? '');
    $duracao = filter_var($_POST['duracao_min'] ?? '', FILTER_VALIDATE_INT);
    $preco = str_replace(',', '.', trim($_POST['preco'] ?? ''));
    try {
        $pdo->beginTransaction();
        if ($acao === 'excluir') {
            // A chave estrangeira impede excluir serviços com histórico.
            $stmt = $pdo->prepare('DELETE FROM servicos WHERE id = :id');
            $stmt->execute([':id' => $id]);
        } elseif ($acao === 'salvar') {
            if ($nome === '' || mb_strlen($nome) > 80 || !$duracao || $duracao < 1
                || !preg_match('/^\d{1,8}(\.\d{1,2})?$/', $preco)) {
                throw new InvalidArgumentException('Informe nome (até 80 caracteres), duração positiva e preço válido.');
            }
            if ($id > 0) {
                $atual = $pdo->prepare('SELECT duracao_min FROM servicos WHERE id = :id FOR UPDATE');
                $atual->execute([':id' => $id]);
                $duracaoAtual = $atual->fetchColumn();
                if ($duracaoAtual === false) {
                    throw new InvalidArgumentException('Serviço não encontrado.');
                }
                if ((int) $duracaoAtual !== $duracao) {
                    $ativos = $pdo->prepare("SELECT COUNT(*) FROM agendamentos WHERE servico_id = :id AND status = 'agendado'");
                    $ativos->execute([':id' => $id]);
                    if ($ativos->fetchColumn() > 0) {
                        throw new RuntimeException('Não altere a duração de um serviço com agendamentos ativos.');
                    }
                }
                $stmt = $pdo->prepare('UPDATE servicos SET nome = :n, duracao_min = :d, preco = :p WHERE id = :id');
                $stmt->execute([':n' => $nome, ':d' => $duracao, ':p' => $preco, ':id' => $id]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO servicos (nome, duracao_min, preco) VALUES (:n, :d, :p)');
                $stmt->execute([':n' => $nome, ':d' => $duracao, ':p' => $preco]);
            }
        } else {
            throw new InvalidArgumentException('Ação inválida.');
        }
        $pdo->commit();
        $_SESSION['flash'] = 'Serviço atualizado com sucesso!';
        header('Location: servicos.php'); exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        error_log($e->getMessage());
        $erro = $e instanceof PDOException
            ? 'Não foi possível salvar ou excluir. Verifique se o nome já existe ou se o serviço possui agendamentos.'
            : $e->getMessage();
        if ($acao === 'salvar') {
            $servico = ['id' => $id, 'nome' => $nome, 'duracao_min' => $_POST['duracao_min'] ?? '', 'preco' => $preco];
        }
    }
} elseif (isset($_GET['id'])) {
    $stmt = $pdo->prepare('SELECT * FROM servicos WHERE id = :id');
    $stmt->execute([':id' => (int) $_GET['id']]);
    $servico = $stmt->fetch();
    if (!$servico) { header('Location: servicos.php'); exit; }
}
$lista = $pdo->query('SELECT * FROM servicos ORDER BY nome')->fetchAll();
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>GlamTime — Serviços</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <?php require_once __DIR__ . '/navbar.php'; ?>
  <main class="container">
    <h4 class="fw-bold">Serviços</h4>
    <?php if ($erro): ?><div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div><?php endif; ?>
    <?php if ($flash): ?><div class="alert alert-success"><?= htmlspecialchars($flash) ?></div><?php endif; ?>
    <form method="post" class="card card-body mb-3">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="id" value="<?= (int) $servico['id'] ?>">
      <input type="hidden" name="acao" value="salvar">
      <div class="mb-2"><label class="form-label">Nome</label><input class="form-control" name="nome" maxlength="80" value="<?= htmlspecialchars($servico['nome']) ?>" required></div>
      <div class="mb-2"><label class="form-label">Duração (minutos)</label><input class="form-control" type="number" name="duracao_min" min="1" value="<?= htmlspecialchars((string) $servico['duracao_min']) ?>" required></div>
      <div class="mb-2"><label class="form-label">Preço (R$)</label><input class="form-control" type="number" name="preco" min="0" step="0.01" value="<?= htmlspecialchars((string) $servico['preco']) ?>" required></div>
      <button class="btn btn-primary">Salvar serviço</button>
      <a href="servicos.php" class="btn btn-link">Novo / limpar</a>
    </form>
    <div class="table-responsive"><table class="table table-hover bg-white">
      <thead><tr><th>Nome</th><th>Duração</th><th>Preço</th><th>Ações</th></tr></thead>
      <tbody><?php foreach ($lista as $s): ?><tr>
        <td><?= htmlspecialchars($s['nome']) ?></td><td><?= (int) $s['duracao_min'] ?> min</td>
        <td>R$ <?= number_format((float) $s['preco'], 2, ',', '.') ?></td>
        <td>
          <a href="servicos.php?id=<?= (int) $s['id'] ?>" class="btn btn-outline-primary btn-sm">Editar</a>
          <form method="post" class="d-inline" onsubmit="return confirm('Excluir serviço?')">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
            <input type="hidden" name="acao" value="excluir">
            <button class="btn btn-outline-danger btn-sm">Excluir</button>
          </form>
        </td>
      </tr><?php endforeach; ?></tbody>
    </table></div>
  </main>
</body>
</html>
