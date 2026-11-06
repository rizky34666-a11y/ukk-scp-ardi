-- Jalankan sekali pada database db_skansaba (alternatif dari `php artisan migrate`)
ALTER TABLE `users`
  ADD COLUMN `email` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL AFTER `nama_lengkap`,
  ADD UNIQUE KEY `users_email_unique` (`email`);

-- Contoh mengisi email akun Waka agar bisa menerima OTP:
-- UPDATE `users` SET `email` = 'emailwaka@gmail.com' WHERE `username` = 'Waka';
