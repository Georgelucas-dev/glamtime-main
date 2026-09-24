<?php $perfil = $_SESSION['usuario_role'] ?? ''; ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php">💇 GlamTime</a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="index.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="agendamentos.php">Agendamentos</a></li>
        <li class="nav-item"><a class="nav-link" href="clientes.php">Clientes</a></li>
        <?php if ($perfil === 'admin'): ?>
          <li class="nav-item"><a class="nav-link" href="servicos.php">Serviços</a></li>
        <?php endif; ?>
      </ul>
      <?php if (isset($_SESSION['usuario_id'])): ?>
      <form method="post" action="logout.php">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'] ?? ($_SESSION['csrf'] = bin2hex(random_bytes(32)))) ?>">
        <button class="btn btn-outline-danger btn-sm">Sair</button>
      </form>
      <?php else: ?>
      <a href="login.php" class="btn btn-outline-light btn-sm">Entrar</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>