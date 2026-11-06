-- Alternatif dari `php artisan migrate` (pilih salah satu saja)
ALTER TABLE `users`
  ADD COLUMN `foto_profil` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL AFTER `no_hp`;
