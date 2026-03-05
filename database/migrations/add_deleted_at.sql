-- Migration: add deleted_at to tables expected by code
ALTER TABLE `orders` ADD COLUMN `deleted_at` TIMESTAMP NULL AFTER `updated_at`;
ALTER TABLE `reviews` ADD COLUMN `deleted_at` TIMESTAMP NULL AFTER `updated_at`;
-- You can run this in phpMyAdmin or mysql CLI while connected to DB-ecommerce database
-- Example (CLI):
-- mysql -u root -p DB-ecommerce < add_deleted_at.sql
