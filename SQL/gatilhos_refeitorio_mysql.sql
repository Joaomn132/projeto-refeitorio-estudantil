-- =====================================================
-- BD do Refeitório Estudantil - GATILHOS (MySQL / MariaDB)
-- Rodar DEPOIS do DDL e ANTES do povoamento.
-- No phpMyAdmin: aba Importar (aceita DELIMITER) ou aba SQL
-- (nesse caso, trocar o campo "Delimitador" para $$).
-- =====================================================

USE refeitorio;

DROP TRIGGER IF EXISTS trg_acesso_bi;
DROP TRIGGER IF EXISTS trg_acesso_bu;
DROP TRIGGER IF EXISTS trg_pagamento_bu;
DROP TRIGGER IF EXISTS trg_pagamento_bd;
DROP TRIGGER IF EXISTS trg_carteirinha_bi;
DROP TRIGGER IF EXISTS trg_refeicao_bu;

DELIMITER $$

-- 1) ACESSO (antes de inserir): valida carteirinha, conta e saldo;
--    o valor debitado é sempre o preço da refeição.
CREATE TRIGGER trg_acesso_bi
BEFORE INSERT ON acesso
FOR EACH ROW
BEGIN
    DECLARE v_status_cart  VARCHAR(10);
    DECLARE v_validade     DATE;
    DECLARE v_id_aluno     INT;
    DECLARE v_status_conta VARCHAR(10);
    DECLARE v_preco        DECIMAL(6,2);
    DECLARE v_saldo        DECIMAL(10,2);

    SELECT status, validade, id_aluno
      INTO v_status_cart, v_validade, v_id_aluno
      FROM carteirinha
     WHERE id_carteirinha = NEW.id_carteirinha;

    IF v_status_cart <> 'ATIVA' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Acesso negado: a carteirinha não está ativa.';
    END IF;

    IF v_validade < CURDATE() THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Acesso negado: a carteirinha está vencida.';
    END IF;

    SELECT status INTO v_status_conta
      FROM conta
     WHERE id_aluno = v_id_aluno;

    IF v_status_conta IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Acesso negado: o aluno não possui conta.';
    END IF;

    IF v_status_conta <> 'ATIVA' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Acesso negado: a conta está bloqueada.';
    END IF;

    -- Garante no máximo 1 acesso por aluno em cada refeição (mesmo após troca de carteirinha)
    IF EXISTS (
        SELECT 1
          FROM acesso a
          JOIN carteirinha ct ON ct.id_carteirinha = a.id_carteirinha
         WHERE ct.id_aluno = v_id_aluno
           AND a.id_refeicao = NEW.id_refeicao
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Acesso negado: este aluno já registrou acesso nesta refeição.';
    END IF;

    SELECT preco INTO v_preco
      FROM refeicao
     WHERE id_refeicao = NEW.id_refeicao;

    SELECT saldo INTO v_saldo
      FROM vw_saldo
     WHERE id_aluno = v_id_aluno;

    IF v_saldo < v_preco THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Acesso negado: saldo insuficiente.';
    END IF;

    SET NEW.valor_debitado = v_preco;
END$$

-- 2) ACESSO (antes de atualizar): o registro de acesso é imutável
--    quanto à carteirinha, à refeição e ao valor debitado.
CREATE TRIGGER trg_acesso_bu
BEFORE UPDATE ON acesso
FOR EACH ROW
BEGIN
    IF NEW.id_carteirinha <> OLD.id_carteirinha
       OR NEW.id_refeicao <> OLD.id_refeicao
       OR NEW.valor_debitado <> OLD.valor_debitado THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Um acesso registrado não pode ter carteirinha, refeição ou valor alterados.';
    END IF;
END$$

-- 3) PAGAMENTO (antes de atualizar): pagamento confirmado não pode ser
--    alterado nem voltar a pendente; cancelado não pode ser reativado nem
--    alterado; cancelar um crédito não pode deixar o saldo negativo.
CREATE TRIGGER trg_pagamento_bu
BEFORE UPDATE ON pagamento
FOR EACH ROW
BEGIN
    DECLARE v_saldo DECIMAL(10,2);

    IF OLD.status = 'CANCELADO' THEN
        IF NEW.status <> 'CANCELADO' THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Um pagamento cancelado não pode ser reativado.';
        END IF;
        IF NEW.valor <> OLD.valor
           OR NEW.id_conta <> OLD.id_conta
           OR NEW.data_hora <> OLD.data_hora
           OR NEW.forma_pagamento <> OLD.forma_pagamento THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Um pagamento cancelado não pode ser alterado.';
        END IF;
    END IF;

    IF OLD.status = 'CONFIRMADO'
       AND (NEW.valor <> OLD.valor
            OR NEW.id_conta <> OLD.id_conta
            OR NEW.data_hora <> OLD.data_hora
            OR NEW.forma_pagamento <> OLD.forma_pagamento
            OR NEW.status = 'PENDENTE') THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Um pagamento confirmado não pode ser alterado.';
    END IF;

    IF OLD.status = 'CONFIRMADO' AND NEW.status = 'CANCELADO' THEN
        SELECT saldo INTO v_saldo
          FROM vw_saldo
         WHERE id_conta = OLD.id_conta;

        IF v_saldo - OLD.valor < 0 THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Cancelamento negado: o saldo da conta ficaria negativo.';
        END IF;
    END IF;
END$$

-- 4) PAGAMENTO (antes de excluir): excluir um pagamento confirmado não pode
--    deixar o saldo da conta negativo.
CREATE TRIGGER trg_pagamento_bd
BEFORE DELETE ON pagamento
FOR EACH ROW
BEGIN
    DECLARE v_saldo DECIMAL(10,2);

    IF OLD.status = 'CONFIRMADO' THEN
        SELECT saldo INTO v_saldo
          FROM vw_saldo
         WHERE id_conta = OLD.id_conta;

        IF v_saldo - OLD.valor < 0 THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Exclusão negada: o saldo da conta ficaria negativo.';
        END IF;
    END IF;
END$$

-- 5) CARTEIRINHA (antes de inserir): só se cadastra carteirinha com validade futura.
CREATE TRIGGER trg_carteirinha_bi
BEFORE INSERT ON carteirinha
FOR EACH ROW
BEGIN
    IF NEW.validade <= CURDATE() THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Não é possível cadastrar uma carteirinha sem validade futura.';
    END IF;
END$$

-- 6) REFEICAO (antes de atualizar): o preço não muda depois que a
--    refeição já teve acessos registrados.
CREATE TRIGGER trg_refeicao_bu
BEFORE UPDATE ON refeicao
FOR EACH ROW
BEGIN
    IF NEW.preco <> OLD.preco
       AND EXISTS (SELECT 1 FROM acesso WHERE id_refeicao = OLD.id_refeicao) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'O preço não pode ser alterado: a refeição já possui acessos registrados.';
    END IF;
END$$

DELIMITER ;

SHOW TRIGGERS;
