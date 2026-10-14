USE restomanager;

INSERT INTO users (id, name, email, phone, password_hash, role, is_active)
VALUES
    (1, 'Admin RestoManager', 'admin@restomanager.local', '081200000001', '$2y$10$k93AUjfi/vpmEQwA.InRKeWs5Vvm45ZUpUQNf4QtdYl.V1MciQh0i', 'admin', 1),
    (2, 'Petugas RestoManager', 'staff@restomanager.local', '081200000002', '$2y$10$YrQgX/9ZfvrNfvNS4w3ss.MjlL3ROqtbVi.sHMLkKuLImXc2sg/.6', 'staff', 1)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    phone = VALUES(phone),
    password_hash = VALUES(password_hash),
    role = VALUES(role),
    is_active = VALUES(is_active);

INSERT INTO categories (id, name, is_active)
VALUES
    (1, 'Makanan', 1),
    (2, 'Minuman', 1),
    (3, 'Camilan', 1)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    is_active = VALUES(is_active);

INSERT INTO dining_tables (id, table_code, status, is_active)
VALUES
    (1, 'M-01', 'available', 1),
    (2, 'M-02', 'available', 1),
    (3, 'M-03', 'available', 1),
    (4, 'M-04', 'available', 1),
    (5, 'M-05', 'available', 1),
    (6, 'M-06', 'available', 1)
ON DUPLICATE KEY UPDATE
    table_code = VALUES(table_code),
    is_active = VALUES(is_active);

INSERT INTO menu_items (category_id, name, description, price_idr, image_path, is_visible, is_available)
VALUES
    (1, 'Nasi Goreng Kampung', 'Nasi goreng gurih dengan telur dan sayuran.', 28000, '/assets/img/menu/nasi-goreng-kampung.webp', 1, 1),
    (1, 'Mie Goreng Jawa', 'Mie goreng manis gurih dengan sayuran.', 26000, '/assets/img/menu/mie-goreng-jawa.webp', 1, 1),
    (1, 'Ayam Bakar Madu', 'Ayam bakar dengan saus madu dan nasi.', 35000, '/assets/img/menu/ayam-bakar-madu.webp', 1, 1),
    (1, 'Soto Ayam', 'Soto ayam hangat dengan nasi.', 25000, '/assets/img/menu/soto-ayam.webp', 1, 1),
    (2, 'Es Teh Manis', 'Teh manis dingin.', 8000, '/assets/img/menu/es-teh-manis.webp', 1, 1),
    (2, 'Jeruk Peras', 'Jeruk peras segar.', 12000, '/assets/img/menu/jeruk-peras.webp', 1, 1),
    (3, 'Pisang Goreng', 'Pisang goreng hangat.', 15000, '/assets/img/menu/pisang-goreng.webp', 1, 1),
    (3, 'Kentang Goreng', 'Kentang goreng renyah.', 18000, '/assets/img/menu/kentang-goreng.webp', 1, 1),
    (2, 'Es Campur', 'Es segar dengan buah, jeli, dan sirup manis yang menyegarkan.', 18000, '/assets/img/menu/es-campur.webp', 1, 1),
    (3, 'Tempe Mendoan', 'Tempe berbalut adonan berbumbu, digoreng lembut dan disajikan hangat.', 16000, '/assets/img/menu/tempe-mendoan.webp', 1, 1)
ON DUPLICATE KEY UPDATE
    category_id = VALUES(category_id),
    name = VALUES(name),
    description = VALUES(description),
    price_idr = VALUES(price_idr),
    image_path = VALUES(image_path),
    is_visible = VALUES(is_visible),
    is_available = VALUES(is_available);
