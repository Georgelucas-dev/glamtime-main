<?php
declare(strict_types=1);

class AgendamentoDAO
{
    public function __construct(private PDO $pdo) {}

    public function listar(): array
    {
        $sql = "SELECT a.id, a.cliente_id, a.servico_id, a.data_hora, a.status,
                       c.nome cliente, s.nome servico
                FROM agendamentos a
                JOIN clientes c ON a.cliente_id = c.id
                JOIN servicos s ON a.servico_id = s.id
                ORDER BY a.data_hora";
        return $this->pdo->query($sql)->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM agendamentos WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function inserir(Agendamento $a): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO agendamentos (cliente_id, servico_id, data_hora, status)
             VALUES (:c, :s, :d, :st)"
        );
        $stmt->execute([
            ':c'  => $a->getClienteId(),
            ':s'  => $a->getServicoId(),
            ':d'  => $a->getDataHora()->format('Y-m-d H:i:s'),
            ':st' => $a->getStatus(),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function cancelar(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE agendamentos SET status = 'cancelado' WHERE id = :id AND status = 'agendado'"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function contarPorStatus(string $status): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM agendamentos WHERE status = :st");
        $stmt->execute([':st' => $status]);
        return (int) $stmt->fetchColumn();
    }

    public function validarDados(int $clienteId, int $servicoId, string $dataHora): array
    {
        $data = DateTime::createFromFormat('!Y-m-d\TH:i', $dataHora);
        if (!$data || $data->format('Y-m-d\TH:i') !== $dataHora) {
            throw new InvalidArgumentException('Informe uma data e hora válidas.');
        }
        if ($data < new DateTime()) {
            throw new InvalidArgumentException('Não é permitido agendar no passado.');
        }
        $cliente = $this->pdo->prepare("SELECT id FROM clientes WHERE id = :id");
        $cliente->execute([':id' => $clienteId]);
        if (!$cliente->fetchColumn()) {
            throw new InvalidArgumentException('Selecione um cliente válido.');
        }
        $servico = $this->pdo->prepare("SELECT duracao_min FROM servicos WHERE id = :id");
        $servico->execute([':id' => $servicoId]);
        $duracao = (int) $servico->fetchColumn();
        if ($duracao <= 0) {
            throw new InvalidArgumentException('Selecione um serviço com duração válida.');
        }
        $fim = clone $data;
        $fim->modify('+' . $duracao . ' minutes');
        return [$data->format('Y-m-d H:i:s'), $fim->format('Y-m-d H:i:s')];
    }

    // Chamar dentro da transação, antes de consultar conflitos.
    // As três ações bloqueiam as mesmas linhas para evitar gravações simultâneas.
    public function bloquearAgenda(): void
    {
        $this->pdo->query("SELECT id FROM servicos ORDER BY id FOR UPDATE")->fetchAll();
    }

    public function verificarConflito(string $inicio, string $fim, ?int $ignorarId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM agendamentos a
                JOIN servicos s ON a.servico_id = s.id
                WHERE a.status = 'agendado'
                  AND :novo_inicio < DATE_ADD(a.data_hora, INTERVAL s.duracao_min MINUTE)
                  AND :novo_fim > a.data_hora";
        $params = [':novo_inicio' => $inicio, ':novo_fim' => $fim];
        if ($ignorarId !== null) {
            $sql .= " AND a.id <> :ignorar_id";
            $params[':ignorar_id'] = $ignorarId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }
}
