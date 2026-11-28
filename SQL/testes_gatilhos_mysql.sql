-- =====================================================
-- TESTES DOS GATILHOS (para os prints do trabalho e para o vídeo)
-- Rodar DEPOIS do povoamento, UM COMANDO POR VEZ no phpMyAdmin
-- (aba SQL). Cada comando marcado "ERRO ESPERADO" deve ser recusado.
-- =====================================================

USE refeitorio;

-- ---------- trg_acesso_bi ----------

-- ERRO ESPERADO: carteirinha bloqueada (aluno 6)
INSERT INTO acesso (id_carteirinha, id_refeicao, valor_debitado) VALUES (6, 7, 6.50);

-- ERRO ESPERADO: saldo insuficiente (aluno 5 só tem pagamento pendente)
INSERT INTO acesso (id_carteirinha, id_refeicao, valor_debitado) VALUES (5, 7, 6.50);

-- ERRO ESPERADO: carteirinha vencida (primeiro vence a carteirinha 4)
UPDATE carteirinha SET validade = '2026-11-01' WHERE id_carteirinha = 4;
INSERT INTO acesso (id_carteirinha, id_refeicao, valor_debitado) VALUES (4, 7, 6.50);
-- desfazer o vencimento:
UPDATE carteirinha SET validade = '2027-07-31' WHERE id_carteirinha = 4;

-- SUCESSO: acesso válido (o valor é trocado pelo preço da refeição, 6.50)
INSERT INTO acesso (id_carteirinha, id_refeicao, valor_debitado) VALUES (4, 7, 1.00);
SELECT id_acesso, id_carteirinha, id_refeicao, valor_debitado FROM acesso ORDER BY id_acesso DESC LIMIT 1;
SELECT * FROM vw_saldo;

-- ---------- trg_acesso_bu ----------

-- ERRO ESPERADO: alterar o valor de um acesso registrado
UPDATE acesso SET valor_debitado = 1.00 WHERE id_acesso = 1;

-- ---------- trg_pagamento_bu ----------

-- ERRO ESPERADO: alterar o valor de um pagamento confirmado
UPDATE pagamento SET valor = 100.00 WHERE id_pagamento = 1;

-- ERRO ESPERADO: alterar a forma de pagamento de um pagamento confirmado
UPDATE pagamento SET forma_pagamento = 'DINHEIRO' WHERE id_pagamento = 1;

-- ERRO ESPERADO: reativar um pagamento cancelado
UPDATE pagamento SET status = 'CONFIRMADO' WHERE id_pagamento = 7;

-- ERRO ESPERADO: cancelar crédito e deixar o saldo negativo (conta 4)
UPDATE pagamento SET status = 'CANCELADO' WHERE id_pagamento = 4;

-- ---------- trg_pagamento_bd ----------

-- ERRO ESPERADO: excluir crédito confirmado e deixar o saldo negativo (conta 4)
DELETE FROM pagamento WHERE id_pagamento = 4;

-- ---------- trg_carteirinha_bi ----------

-- ERRO ESPERADO: carteirinha com validade no passado
INSERT INTO carteirinha (id_aluno, codigo, validade, status)
VALUES (5, 'CART-0099', '2020-01-01', 'INATIVA');

-- ERRO ESPERADO: carteirinha sem validade futura (data de hoje)
INSERT INTO carteirinha (id_aluno, codigo, validade, status)
VALUES (5, 'CART-0097', CURDATE(), 'INATIVA');

-- ---------- trg_refeicao_bu ----------

-- ERRO ESPERADO: mudar o preço de uma refeição que já teve acessos
UPDATE refeicao SET preco = 8.00 WHERE id_refeicao = 1;

-- ---------- Regra estrutural (DDL): uma carteirinha ATIVA por aluno ----------

-- ERRO ESPERADO: segunda carteirinha ativa para o aluno 5
INSERT INTO carteirinha (id_aluno, codigo, validade, status)
VALUES (5, 'CART-0098', '2027-12-31', 'ATIVA');
