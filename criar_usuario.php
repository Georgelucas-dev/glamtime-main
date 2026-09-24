<?php
declare(strict_types=1);

// Criação de usuários restrita a administradores autenticados.

require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/auth_check.php';
if (($_SESSION['usuario_role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Somente administradores podem criar usuários por esta página.');
}

/**
 * Valida a força da senha conforme política OWASP mínima.
 * Regra: ≥ 8 caracteres + maiúscula + minúscula + dígito (+ símbolo recomendado).
 * @return string|null null = válida | string = mensagem do motivo da rejeição
 */
function validarForcaSenha(string $senha): ?string
{
    if (mb_strlen($senha) < 8) {
        return 'A senha deve ter no mínimo 8 caracteres.';
    }
    if (!preg_match('/[A-Z]/', $senha)) {
        return 'A senha deve conter ao menos uma letra maiúscula.';
    }
    if (!preg_match('/[a-z]/', $senha)) {
        return 'A senha deve conter ao menos uma letra minúscula.';
    }
    if (!preg_match('/\d/', $senha)) {
        return 'A senha deve conter ao menos um dígito.';
    }
    // Senhas do dicionário comuns (Bloco 4: discussão sobre credential stuffing)
    if (in_array(strtolower($senha), ['admin1234', 'senha1234', 'password1'], true)) {
        return 'Esta senha está em lista de senhas comprometidas comuns.';
    }
    return null;
}

$erros = [];
$sucesso = null;
$nome  = $email = $role = '';
$senha = $confirmar = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_SESSION['csrf']) || !is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
        http_response_code(403);
        exit('Token CSRF inválido. Volte e recarregue o formulário.');
    }
    $nome     = trim($_POST['nome'] ?? '');
    $email    = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL) ?: '';
    $role     = ($_POST['role'] ?? 'recepcionista') === 'admin' ? 'admin' : 'recepcionista';
    $senha     = $_POST['senha'] ?? '';
    $confirmar = $_POST['confirmar'] ?? '';

    if ($nome === '') {
        $erros[] = 'Informe o nome.';
    }
    if (!$email) {
        $erros[] = 'Informe um e-mail válido.';
    }
    if ($motivo = validarForcaSenha($senha)) {
        $erros[] = $motivo;
    }
    if ($senha !== $confirmar) {
        $erros[] = 'A confirmação não corresponde à senha.';
    }

    if (!$erros) {
        // E-mail único: checa antes de inserir (feedback melhor que erro da constraint)
        $check = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email");
        $check->execute([':email' => $email]);
        if ($check->fetch()) {
            $erros[] = 'Este e-mail já está cadastrado.';
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO usuarios (nome, email, senha_hash, role)
                 VALUES (:nome, :email, :hash, :role)"
            );
            $stmt->execute([
                ':nome'  => $nome,
                ':email' => $email,
                ':hash'  => password_hash($senha, PASSWORD_DEFAULT), // BCRYPT, cost 10
                ':role'  => $role,
            ]);
            $sucesso = 'Usuário criado com ID ' . $pdo->lastInsertId()
                     . '.';
            $nome = $email = ''; // limpa o formulário
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>GlamTime — Criar usuário</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container" style="max-width:480px">
    <div class="card shadow-sm mt-5">
      <div class="card-body">
        <h4 class="fw-bold mb-3">💇 GlamTime — Criar usuário</h4>

        <?php foreach ($erros as $e): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>

        <?php if ($sucesso): ?>
          <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
        <?php endif; ?>

        <form method="post">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'] ?? ($_SESSION['csrf'] = bin2hex(random_bytes(32)))) ?>">
          <div class="mb-2"><input class="form-control" name="nome" placeholder="Nome" value="<?= htmlspecialchars($nome) ?>" required></div>
          <div class="mb-2"><input class="form-control" type="email" name="email" placeholder="E-mail" value="<?= htmlspecialchars($email) ?>" required></div>
          <div class="mb-2"><select class="form-select" name="role">
            <option value="recepcionista">Recepcionista</option>
            <option value="admin">Admin</option>
          </select></div>
          <div class="mb-2"><input class="form-control" type="password" name="senha" placeholder="Senha (mín. 8 + Aa + dígito)" required></div>
          <div class="mb-2"><input class="form-control" type="password" name="confirmar" placeholder="Confirmar senha" required></div>
          <button class="btn btn-primary w-100">Criar usuário</button>
        </form>
      </div>
    </div>
  </div>
</body>
</html>