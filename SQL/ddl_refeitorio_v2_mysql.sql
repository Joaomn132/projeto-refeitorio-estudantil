-- =====================================================
-- BD do Refeitório Estudantil - DDL (MySQL / MariaDB)
-- Versão do grupo: sem UNIQUE em carteirinha.id_aluno e com
-- regra de "uma carteirinha ATIVA por aluno"
-- =====================================================

DROP DATABASE IF EXISTS refeitorio;
CREATE DATABASE refeitorio CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE refeitorio;

CREATE TABLE aluno (
    id_aluno   INT AUTO_INCREMENT,
    matricula  VARCHAR(20)  NOT NULL,
    nome       VARCHAR(100) NOT NULL,
    curso      VARCHAR(60)  NOT NULL,
    email      VARCHAR(100) NOT NULL,
    CONSTRAINT pk_aluno PRIMARY KEY (id_aluno),
    CONSTRAINT uq_aluno_matricula UNIQUE (matricula),
    CONSTRAINT uq_aluno_email UNIQUE (email)
) ENGINE=InnoDB;

-- 2) CARTEIRINHA
CREATE TABLE carteirinha (
    id_carteirinha INT AUTO_INCREMENT,
    id_aluno       INT           NOT NULL,
    codigo         VARCHAR(30)   NOT NULL,
    validade       DATE          NOT NULL,
    status         VARCHAR(10)   NOT NULL DEFAULT 'ATIVA',
    CONSTRAINT pk_carteirinha PRIMARY KEY (id_carteirinha),
    CONSTRAINT fk_cart_aluno FOREIGN KEY (id_aluno) REFERENCES aluno (id_aluno),
    CONSTRAINT uq_cart_codigo UNIQUE (codigo),
    CONSTRAINT ck_cart_status CHECK (status IN ('ATIVA', 'INATIVA', 'BLOQUEADA'))
) ENGINE=InnoDB;

-- 3) CONTA (1:1 com ALUNO - relacionamento POSSUI)
CREATE TABLE conta (
    id_conta INT AUTO_INCREMENT,
    id_aluno INT         NOT NULL,
    status   VARCHAR(10) NOT NULL DEFAULT 'ATIVA',
    CONSTRAINT pk_conta PRIMARY KEY (id_conta),
    CONSTRAINT fk_conta_aluno FOREIGN KEY (id_aluno) REFERENCES aluno (id_aluno),
    CONSTRAINT uq_conta_aluno UNIQUE (id_aluno),           -- garante 1:1
    CONSTRAINT ck_conta_status CHECK (status IN ('ATIVA', 'BLOQUEADA'))
) ENGINE=InnoDB;

-- 4) REFEICAO
CREATE TABLE refeicao (
    id_refeicao INT AUTO_INCREMENT,
    `data`      DATE          NOT NULL,
    tipo        VARCHAR(10)   NOT NULL,
    cardapio    VARCHAR(200)  NOT NULL,
    preco       DECIMAL(6,2)  NOT NULL,
    CONSTRAINT pk_refeicao PRIMARY KEY (id_refeicao),
    CONSTRAINT uq_refeicao_data_tipo UNIQUE (`data`, tipo),  -- um almoço e um jantar por dia
    CONSTRAINT ck_refeicao_tipo CHECK (tipo IN ('ALMOCO', 'JANTAR')),
    CONSTRAINT ck_refeicao_preco CHECK (preco > 0)
) ENGINE=InnoDB;

-- 5) PAGAMENTO (N:1 com CONTA - relacionamento RECEBE_CREDITOS)
CREATE TABLE pagamento (
    id_pagamento    INT AUTO_INCREMENT,
    id_conta        INT           NOT NULL,
    valor           DECIMAL(8,2)  NOT NULL,
    data_hora       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    forma_pagamento VARCHAR(10)   NOT NULL,
    status          VARCHAR(10)   NOT NULL DEFAULT 'PENDENTE',
    CONSTRAINT pk_pagamento PRIMARY KEY (id_pagamento),
    CONSTRAINT fk_pag_conta FOREIGN KEY (id_conta) REFERENCES conta (id_conta),
    CONSTRAINT ck_pag_valor CHECK (valor > 0),
    CONSTRAINT ck_pag_forma CHECK (forma_pagamento IN ('PIX', 'CARTAO', 'DINHEIRO')),
    CONSTRAINT ck_pag_status CHECK (status IN ('PENDENTE', 'CONFIRMADO', 'CANCELADO'))
) ENGINE=InnoDB;

-- 6) ACESSO (N:1 com CARTEIRINHA - IDENTIFICA; N:1 com REFEICAO - REGISTRA)
CREATE TABLE acesso (
    id_acesso      INT AUTO_INCREMENT,
    id_carteirinha INT          NOT NULL,
    id_refeicao    INT          NOT NULL,
    data_hora      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    valor_debitado DECIMAL(6,2) NOT NULL,
    CONSTRAINT pk_acesso PRIMARY KEY (id_acesso),
    CONSTRAINT fk_acesso_cart FOREIGN KEY (id_carteirinha) REFERENCES carteirinha (id_carteirinha),
    CONSTRAINT fk_acesso_ref FOREIGN KEY (id_refeicao) REFERENCES refeicao (id_refeicao),
    CONSTRAINT uq_acesso_cart_ref UNIQUE (id_carteirinha, id_refeicao),  -- 1 acesso por refeição
    CONSTRAINT ck_acesso_valor CHECK (valor_debitado > 0)
) ENGINE=InnoDB;

-- Índices nas chaves estrangeiras (consultas e relatórios)
CREATE INDEX ix_pag_conta   ON pagamento (id_conta);
CREATE INDEX ix_acesso_ref  ON acesso (id_refeicao);

-- 7) VIEW: saldo calculado (créditos confirmados - débitos de acessos)
CREATE OR REPLACE VIEW vw_saldo AS
SELECT c.id_conta,
       c.id_aluno,
       IFNULL((SELECT SUM(p.valor)
                 FROM pagamento p
                WHERE p.id_conta = c.id_conta
                  AND p.status = 'CONFIRMADO'), 0)
     - IFNULL((SELECT SUM(a.valor_debitado)
                 FROM acesso a
                 JOIN carteirinha ct ON ct.id_carteirinha = a.id_carteirinha
                WHERE ct.id_aluno = c.id_aluno), 0) AS saldo
  FROM conta c;

-- 8) Regra: no máximo uma carteirinha ATIVA por aluno
-- (equivalente ao índice único por expressão do Oracle; o MariaDB do XAMPP
-- não aceita CASE direto no índice, então usa-se uma coluna gerada)
ALTER TABLE carteirinha
    ADD COLUMN aluno_ativo INT AS (CASE WHEN status = 'ATIVA' THEN id_aluno END) VIRTUAL;

CREATE UNIQUE INDEX uq_cart_aluno_ativa ON carteirinha (aluno_ativo);
