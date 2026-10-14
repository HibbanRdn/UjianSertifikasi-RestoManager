USE restomanager;

-- Data patch idempotent untuk instalasi yang sudah berjalan. Tidak mengubah
-- delapan menu lama dan tidak menghapus histori transaksi.
INSERT INTO menu_items (category_id, name, description, price_idr, image_path, is_visible, is_available)
SELECT c.id, 'Es Campur', 'Es segar dengan buah, jeli, dan sirup manis yang menyegarkan.', 18000,
       '/assets/img/menu/es-campur.webp', 1, 1
FROM categories c
WHERE c.name = 'Minuman'
  AND NOT EXISTS (
      SELECT 1 FROM menu_items m WHERE m.category_id = c.id AND m.name = 'Es Campur'
  );

INSERT INTO menu_items (category_id, name, description, price_idr, image_path, is_visible, is_available)
SELECT c.id, 'Tempe Mendoan', 'Tempe berbalut adonan berbumbu, digoreng lembut dan disajikan hangat.', 16000,
       '/assets/img/menu/tempe-mendoan.webp', 1, 1
FROM categories c
WHERE c.name = 'Camilan'
  AND NOT EXISTS (
      SELECT 1 FROM menu_items m WHERE m.category_id = c.id AND m.name = 'Tempe Mendoan'
  );
