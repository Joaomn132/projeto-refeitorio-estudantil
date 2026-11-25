# Sistema do Refeitório Estudantil

Trabalho prático de Banco de Dados I e Laboratório de Banco de Dados I (CEFET-MG, 2º semestre de 2026).
Sistema web em PHP com MySQL para controlar o acesso, os pagamentos e as refeições de um refeitório estudantil.

**Integrantes:** (preencher com os nomes do grupo)

## Estrutura

```
.
├── conexao.php      # conexão com o MySQL (mysqli)
├── config.php       # funções e descrição das 6 tabelas
├── header.php       # cabeçalho e menu
├── footer.php
├── index.php        # página inicial
├── crud.php         # CRUD genérico das 6 tabelas
├── relatorios.php   # consultas: junção interna/externa, GROUP BY, HAVING, subconsulta
└── sql/
    ├── ddl_refeitorio_v2_mysql.sql        # criação do banco e das tabelas
    ├── gatilhos_refeitorio_mysql.sql      # 5 gatilhos
    ├── povoamento_refeitorio_mysql.sql    # dados de exemplo
    └── testes_gatilhos_mysql.sql          # comandos para testar as regras
```

## Como rodar

1. Instale o [XAMPP](https://www.apachefriends.org/) (Apache, PHP e MySQL) e inicie o Apache e o MySQL.
2. Copie este projeto para `C:\xampp\htdocs\refeitorio\`.
3. No phpMyAdmin (`localhost/phpmyadmin`), importe, nesta ordem:
   1. `sql/ddl_refeitorio_v2_mysql.sql`
   2. `sql/gatilhos_refeitorio_mysql.sql`
   3. `sql/povoamento_refeitorio_mysql.sql`
4. Abra `localhost/refeitorio/`.

A conexão usa o usuário `root` sem senha (padrão do XAMPP). Se o seu MySQL tiver outra configuração, ajuste o `conexao.php`.

## Banco de dados

Tabelas: `aluno`, `carteirinha`, `conta`, `refeicao`, `pagamento`, `acesso`, mais a view `vw_saldo`
(o saldo da conta é calculado: pagamentos confirmados menos os valores debitados nos acessos).

Gatilhos:

- `trg_acesso_bi`: valida carteirinha ativa e dentro da validade, conta ativa e saldo suficiente; grava o preço da refeição como valor debitado.
- `trg_acesso_bu`: um acesso registrado não pode ter carteirinha, refeição ou valor alterados.
- `trg_pagamento_bu`: pagamento confirmado não pode ser alterado; cancelado não é reativado; cancelar crédito não pode deixar o saldo negativo.
- `trg_carteirinha_bi`: não se cadastra carteirinha com validade no passado.
- `trg_refeicao_bu`: o preço não muda depois que a refeição já teve acessos.
