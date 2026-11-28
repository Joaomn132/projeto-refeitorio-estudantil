# Sistema do Refeitório Estudantil

Trabalho prático de Banco de Dados I e Laboratório de Banco de Dados I (CEFET-MG, 2º semestre de 2026).
Sistema web em PHP com MySQL para controlar o acesso, os pagamentos e as refeições de um refeitório estudantil.

**Integrantes:** Carlos Corlaite, Gabriel Carvalho, Gabriel Lucas e João Antônio.

## Estrutura

```
.
├── conexao.php      # conexão com o MySQL (mysqli)
├── config.php       # funções e descrição das 6 tabelas
├── header.php       # cabeçalho e menu
├── footer.php
├── index.php        # área pública do estudante (cardápio e consulta de conta)
├── login.php        # acesso administrativo
├── logout.php       # encerramento de sessão
├── admin.php        # painel de métricas e pagamentos pendentes
├── catraca.php      # registro rápido de acesso na portaria
├── crud.php         # CRUD genérico das 6 tabelas
├── relatorios.php   # consultas: junção interna/externa, GROUP BY, HAVING, subconsulta
└── SQL/
    ├── ddl_refeitorio_v2_mysql.sql        # criação do banco e das tabelas
    ├── gatilhos_refeitorio_mysql.sql      # 6 gatilhos
    ├── povoamento_refeitorio_mysql.sql    # dados de exemplo
    └── testes_gatilhos_mysql.sql          # comandos para testar as regras
```

## Como rodar

1. Instale o [XAMPP](https://www.apachefriends.org/) (Apache, PHP e MySQL) e inicie o Apache e o MySQL.
2. Copie ou clone este projeto dentro de `C:\xampp\htdocs\` (por exemplo, `C:\xampp\htdocs\refeitorio\`).
3. No phpMyAdmin (`localhost/phpmyadmin`), importe, nesta ordem:
   1. `SQL/ddl_refeitorio_v2_mysql.sql`
   2. `SQL/gatilhos_refeitorio_mysql.sql`
   3. `SQL/povoamento_refeitorio_mysql.sql`
4. Abra `localhost/nome-da-pasta-do-projeto/` no navegador (por exemplo, `localhost/refeitorio/` se a pasta se chamar `refeitorio`).

A conexão usa o usuário `root` sem senha (padrão do XAMPP). Se o seu MySQL tiver outra configuração, ajuste o `conexao.php`.

## Banco de dados

Tabelas: `aluno`, `carteirinha`, `conta`, `refeicao`, `pagamento`, `acesso`, mais a view `vw_saldo`
(o saldo da conta é calculado: pagamentos confirmados menos os valores debitados nos acessos).

Gatilhos:

- `trg_acesso_bi`: valida carteirinha ativa e dentro da validade, conta ativa, no máximo um acesso por aluno na refeição e saldo suficiente; grava o preço da refeição como valor debitado.
- `trg_acesso_bu`: um acesso registrado não pode ter carteirinha, refeição ou valor alterados.
- `trg_pagamento_bu`: pagamento confirmado não pode ser alterado (exceto para cancelamento); cancelado não é reativado nem alterado; cancelar crédito não pode deixar o saldo negativo.
- `trg_pagamento_bd`: excluir pagamento confirmado não pode deixar o saldo negativo.
- `trg_carteirinha_bi`: só se cadastra carteirinha com validade futura.
- `trg_refeicao_bu`: o preço não muda depois que a refeição já teve acessos.
