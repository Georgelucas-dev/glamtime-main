# Relatório simples — Correções do GlamTime

O projeto foi corrigido mantendo PHP, PDO, MySQL, Bootstrap e a estrutura de páginas existente. A DAO que já existia foi reutilizada para verificar os conflitos de horário.

## O que foi mudado

- Corrigidas as consultas com parâmetros repetidos e a assinatura de `verificarConflito()`. A edição agora ignora o próprio agendamento na comparação de horários.
- Corrigido o nome do arquivo de reagendamento e completado seu formulário. Foi mantida a regra original: criar um novo agendamento e concluir o anterior.
- Implementado o logout, com limpeza da sessão e do cookie.
- Adicionadas verificações de login, perfil de administrador e token CSRF nas rotas que estavam sem proteção.
- Corrigido o tratamento de e-mail inválido nos cadastros para evitar erro de tipo.
- Centralizada a validação de data, cliente e serviço. Adicionada transação na edição e bloqueio comum às três ações da agenda para reduzir problemas com gravações simultâneas.
- Criada a página de serviços que era referenciada pelo menu, com operações restritas a administradores.
- Substituídos os marcadores de senha do SQL por hashes válidos para as contas de demonstração.
- Incluído o JavaScript do Bootstrap para o menu mobile e a meta viewport nas páginas.
- Ajustes menores: exibição das mensagens de login, mensagens de erro da agenda em vermelho, tratamento de telefone/e-mail nulos na listagem de clientes e verificação da existência do `.env` em `Database.php`.

## Onde tirar os prints

Abra **GUIA-DE-PRINTS.html** para ver os trechos lado a lado. As linhas abaixo se referem às versões identificadas; podem mudar se você editar os arquivos.

| Nº | Erro | Original: arquivo e linhas | Corrigido: arquivo e linhas |
|---|---|---|---|
| 1 | Consulta de conflito com parâmetro repetido | `agendamento_salvar.php` 17–27 | `agendamento_salvar.php` 13–20 |
| 2 | DAO incompatível com edição e reagendamento | `src/AgendamentoDAO.php` 57–69 | `src/AgendamentoDAO.php` 89–103 |
| 3 | Link de reagendamento quebrado e formulário incompleto | `agentamento_agendar_novamente.php` 43–44 | `agendamento_agendar_novamente.php` 56–70 |
| 4 | Logout vazio | `logout.php` (vazio) | `logout.php` 1–16 |
| 5 | Rotas de gravação sem login e CSRF vazio | `agendamento_cancelar.php` 1–8 | `agendamento_cancelar.php` 1–9 |
| 6 | Cadastro sem validar o token | `registro.php` 27–28 | `registro.php` 27–32 |
| 7 | E-mail inválido causando TypeError | `registro.php` 38–41 | `registro.php` 42–45 |
| 8 | Criação de administrador sem autorização | `criar_usuario.php` 11–24 | `criar_usuario.php` 6–11 |
| 9 | API sem autenticação | `api/agendamentos.php` 1–9 | `api/agendamentos.php` 1–10 |
| 10 | Menu de serviços sem página | `navbar.php` 13–15 | `servicos.php` 1–8 |
| 11 | Login das contas de exemplo não funcionava | `sql/glamtime.sql` 62–64 | `sql/glamtime.sql` 61–63 |
| 12 | Datas e IDs sem validação consistente | `agendamento_editar.php` 23–27 | `src/AgendamentoDAO.php` 57–79 |
| 13 | Menu mobile não abria | `navbar.php` 20–20 | `navbar.php` 27–28 |

## Como usar

1. Extraia o ZIP e coloque a pasta `glamtime-main` no `htdocs` do XAMPP.
2. Em um banco novo, importe `sql/glamtime.sql`. Se o banco original já existe, não reimporte: execute apenas `sql/corrigir_hashes_exemplo.sql` para corrigir os marcadores de senha.
3. Configure a conexão conforme `readme.md` e inicie Apache/MySQL. É necessário PHP 8+ com PDO MySQL e mbstring.
4. Acesse `http://localhost/glamtime-main/`. Contas de demonstração: `admin@glamtime.com` / `admin` e `recepcao@glamtime.com` / `recep123`. Senhas previamente configuradas no banco não são alteradas pelo SQL de correção.

## Verificação realizada e limite

Foram analisados estaticamente 22 arquivos PHP com Tree-sitter, sem erros de parsing. Também foram inspecionadas 40 consultas SQL quanto a nomes de parâmetros repetidos e 18 links/ações locais, sem os problemas procurados. Os hashes de demonstração foram verificados com bcrypt.

Não foi possível executar PHP/MySQL neste ambiente. Isso não substitui `php -l` nem testes de execução. O fluxo completo, a interface e o comportamento de concorrência precisam ser confirmados no XAMPP.

Teste local sugerido: entrar e sair; criar um horário futuro; tentar outro sobreposto; editar sem alterar o horário; cancelar; reagendar; enviar uma data inválida; cadastrar um e-mail inválido; abrir Serviços como admin e como recepcionista; acessar a API sem login. Para testar conflitos, use o mesmo dia e horários que considerem a duração do serviço.
