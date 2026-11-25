-- =====================================================
-- BD do Refeitório Estudantil - POVOAMENTO (MySQL / MariaDB)
-- Rodar DEPOIS do DDL e dos GATILHOS.
-- Ordem: aluno, carteirinha, conta, refeicao, pagamento, acesso
-- O gatilho trg_acesso_bi valida cada acesso (carteirinha, conta, saldo)
-- e grava em valor_debitado o preço da refeição.
-- =====================================================

USE refeitorio;

-- 1) ALUNO (6)
INSERT INTO aluno (matricula, nome, curso, email) VALUES
('20230001', 'Ana Beatriz Souza',   'Engenharia de Computação', 'ana.souza@aluno.cefetmg.br'),
('20230002', 'Bruno Henrique Lima', 'Engenharia de Computação', 'bruno.lima@aluno.cefetmg.br'),
('20220015', 'Carla Dias Mendes',   'Engenharia Elétrica',      'carla.mendes@aluno.cefetmg.br'),
('20220031', 'Daniel Rocha Pereira','Engenharia Mecânica',      'daniel.pereira@aluno.cefetmg.br'),
('20240007', 'Eduarda Nunes Alves', 'Engenharia Civil',         'eduarda.alves@aluno.cefetmg.br'),
('20240019', 'Felipe Araújo Costa', 'Engenharia Elétrica',      'felipe.costa@aluno.cefetmg.br');

-- 2) CARTEIRINHA (6) - a do aluno 6 está bloqueada
INSERT INTO carteirinha (id_aluno, codigo, validade, status) VALUES
(1, 'CART-0001', '2027-07-31', 'ATIVA'),
(2, 'CART-0002', '2027-07-31', 'ATIVA'),
(3, 'CART-0003', '2027-12-31', 'ATIVA'),
(4, 'CART-0004', '2027-07-31', 'ATIVA'),
(5, 'CART-0005', '2027-12-31', 'ATIVA'),
(6, 'CART-0006', '2027-07-31', 'BLOQUEADA');

-- 3) CONTA (6) - a conta do aluno 6 está bloqueada
INSERT INTO conta (id_aluno, status) VALUES
(1, 'ATIVA'),
(2, 'ATIVA'),
(3, 'ATIVA'),
(4, 'ATIVA'),
(5, 'ATIVA'),
(6, 'BLOQUEADA');

-- 4) REFEICAO (7)
INSERT INTO refeicao (`data`, tipo, cardapio, preco) VALUES
('2026-11-16', 'ALMOCO', 'Arroz, feijão, frango grelhado, salada e fruta',       6.50),
('2026-11-16', 'JANTAR', 'Macarrão ao molho, carne moída, legumes e suco',        5.50),
('2026-11-17', 'ALMOCO', 'Arroz, feijão, bife acebolado, farofa e salada',        6.50),
('2026-11-17', 'JANTAR', 'Sopa de legumes, pão e fruta',                          5.50),
('2026-11-18', 'ALMOCO', 'Arroz, feijão, peixe assado, purê e salada',            6.50),
('2026-11-18', 'JANTAR', 'Arroz, feijão tropeiro, linguiça e couve',              5.50),
('2026-11-25', 'ALMOCO', 'Arroz, feijão, frango ao molho, batata e salada',       6.50);

-- 5) PAGAMENTO (8)
INSERT INTO pagamento (id_conta, valor, data_hora, forma_pagamento, status) VALUES
(1, 50.00, '2026-11-10 09:15:00', 'PIX',      'CONFIRMADO'),
(2, 30.00, '2026-11-10 10:02:00', 'CARTAO',   'CONFIRMADO'),
(3, 40.00, '2026-11-11 08:40:00', 'DINHEIRO', 'CONFIRMADO'),
(4, 25.00, '2026-11-12 14:20:00', 'PIX',      'CONFIRMADO'),
(5, 20.00, '2026-11-12 16:05:00', 'PIX',      'PENDENTE'),
(1, 20.00, '2026-11-15 11:30:00', 'CARTAO',   'CONFIRMADO'),
(2, 15.00, '2026-11-16 18:10:00', 'PIX',      'CANCELADO'),
(6, 10.00, '2026-11-13 09:00:00', 'PIX',      'CONFIRMADO');

-- 6) ACESSO (10) - alunos 5 e 6 ficam sem acesso (útil no LEFT JOIN)
INSERT INTO acesso (id_carteirinha, id_refeicao, data_hora, valor_debitado) VALUES
(1, 1, '2026-11-16 12:10:00', 6.50),
(1, 2, '2026-11-16 19:05:00', 5.50),
(1, 3, '2026-11-17 12:20:00', 6.50),
(2, 1, '2026-11-16 12:25:00', 6.50),
(2, 3, '2026-11-17 12:30:00', 6.50),
(3, 1, '2026-11-16 12:40:00', 6.50),
(3, 4, '2026-11-17 19:10:00', 5.50),
(4, 5, '2026-11-18 12:15:00', 6.50),
(1, 5, '2026-11-18 12:35:00', 6.50),
(2, 2, '2026-11-16 19:20:00', 5.50);

-- Conferência
SELECT COUNT(*) AS alunos       FROM aluno;
SELECT COUNT(*) AS carteirinhas FROM carteirinha;
SELECT COUNT(*) AS contas       FROM conta;
SELECT COUNT(*) AS refeicoes    FROM refeicao;
SELECT COUNT(*) AS pagamentos   FROM pagamento;
SELECT COUNT(*) AS acessos      FROM acesso;
SELECT * FROM vw_saldo;
