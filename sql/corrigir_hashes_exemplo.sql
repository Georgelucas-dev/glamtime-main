-- Use somente se o banco original já foi importado.
-- Atualiza apenas os marcadores, sem alterar senhas já configuradas.
USE glamtime;
UPDATE usuarios SET senha_hash = '$2y$10$mHRFu6O8fts/cRhSAJ/K4.Ztrt4HpyCauhTssgVM0wuWi/XQrmZIK' WHERE email = 'admin@glamtime.com' AND senha_hash = '[GERAR_HASH_ADMIN]';
UPDATE usuarios SET senha_hash = '$2y$10$TuuoCCC3RKLNyg3pBeE3oezEkIxKfU5ylQeWED29T8.8FAqsXkVuy' WHERE email = 'recepcao@glamtime.com' AND senha_hash = '[GERAR_HASH_RECEP]';
