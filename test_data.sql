UPDATE users SET password = '$2y$10$VzKKGVWWrpqtvF5UqgxKEey1pVgPeKCqVvs5p8Q3V6pV6xY6K6yNi' WHERE email = 'admin@gmail.com';

INSERT INTO categories (id, name, description, image) VALUES (1, 'Electronics', 'Test Category', 'cat1.jpg') ON DUPLICATE KEY UPDATE id=1;

INSERT INTO products (id, seller_id, category_id, name, description, price, stock, status) VALUES (1, 3, 1, 'Test Product', 'Demo', 100000, 50, 'active') ON DUPLICATE KEY UPDATE id=1;
