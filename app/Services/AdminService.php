<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\CheckoutException;
use PDO;
use PDOException;

final class AdminService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly MenuImageUploadService $images
    )
    {
    }

    public function saveCategory(?int $id, string $name): void
    {
        $name = trim($name);
        if ($name === '' || mb_strlen($name) > 100) {
            throw new CheckoutException('Nama kategori wajib diisi maksimal 100 karakter.');
        }
        try {
            if ($id === null) {
                $statement = $this->pdo->prepare('INSERT INTO categories(name,is_active) VALUES(:name,1)');
                $statement->execute(['name' => $name]);
            } else {
                $statement = $this->pdo->prepare('UPDATE categories SET name=:name WHERE id=:id');
                $statement->execute(['name' => $name, 'id' => $id]);
            }
        } catch (PDOException $exception) {
            throw new CheckoutException($exception->getCode() === '23000' ? 'Nama kategori sudah digunakan.' : 'Kategori belum dapat disimpan.');
        }
    }

    public function toggleCategory(int $id): void
    {
        $statement = $this->pdo->prepare('UPDATE categories SET is_active=1-is_active WHERE id=:id');
        $statement->execute(['id' => $id]);
    }

    /** @param array<string,string> $data @param array<string,mixed>|null $image */
    public function saveMenu(?int $id, array $data, ?array $image = null): void
    {
        $categoryId = $this->positiveId($data['category_id'] ?? '');
        $name = trim($data['name'] ?? '');
        $description = trim($data['description'] ?? '');
        $price = filter_var($data['price_idr'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if ($name === '' || mb_strlen($name) > 150 || $price === false || mb_strlen($description) > 1000) {
            throw new CheckoutException('Data menu belum valid. Periksa nama, harga, kategori, dan deskripsi.');
        }

        $existingImage = '/assets/img/menu-placeholder.svg';
        if ($id !== null) {
            $current = $this->pdo->prepare('SELECT image_path FROM menu_items WHERE id=:id');
            $current->execute(['id' => $id]);
            $existing = $current->fetchColumn();
            if (!is_string($existing)) {
                throw new CheckoutException('Menu yang akan diperbarui tidak ditemukan.');
            }
            $existingImage = $existing;
        }

        $uploadedImage = $this->images->store($image);
        $imagePath = $uploadedImage ?? $existingImage;
        $params = ['category_id' => $categoryId, 'name' => $name, 'description' => $description, 'price' => $price, 'image' => $imagePath];
        try {
            if ($id === null) {
                $statement = $this->pdo->prepare('INSERT INTO menu_items(category_id,name,description,price_idr,image_path,is_visible,is_available) VALUES(:category_id,:name,:description,:price,:image,1,1)');
            } else {
                $statement = $this->pdo->prepare('UPDATE menu_items SET category_id=:category_id,name=:name,description=:description,price_idr=:price,image_path=:image WHERE id=:id');
                $params['id'] = $id;
            }
            $statement->execute($params);
        } catch (PDOException $exception) {
            if ($uploadedImage !== null) {
                $this->images->remove($uploadedImage);
            }
            throw new CheckoutException($exception->getCode() === '23000' ? 'Nama menu sudah digunakan pada kategori tersebut.' : 'Menu belum dapat disimpan.');
        }
    }

    public function toggleMenu(int $id, string $field): void
    {
        if (!in_array($field, ['is_visible', 'is_available'], true)) {
            throw new CheckoutException('Aksi menu tidak valid.');
        }
        $statement = $this->pdo->prepare("UPDATE menu_items SET $field=1-$field WHERE id=:id");
        $statement->execute(['id' => $id]);
    }

    public function saveTable(?int $id, string $code): void
    {
        $code = strtoupper(trim($code));
        if (!preg_match('/^[A-Z0-9-]{1,20}$/', $code)) {
            throw new CheckoutException('Kode meja hanya boleh berisi huruf, angka, atau tanda hubung.');
        }
        try {
            if ($id === null) {
                $statement = $this->pdo->prepare("INSERT INTO dining_tables(table_code,status,is_active) VALUES(:code,'available',1)");
                $statement->execute(['code' => $code]);
            } else {
                $statement = $this->pdo->prepare('UPDATE dining_tables SET table_code=:code WHERE id=:id');
                $statement->execute(['code' => $code, 'id' => $id]);
            }
        } catch (PDOException $exception) {
            throw new CheckoutException($exception->getCode() === '23000' ? 'Kode meja sudah digunakan.' : 'Meja belum dapat disimpan.');
        }
    }

    public function toggleTable(int $id): void
    {
        $statement = $this->pdo->prepare("UPDATE dining_tables SET is_active=1-is_active WHERE id=:id AND status='available'");
        $statement->execute(['id' => $id]);
        if ($statement->rowCount() !== 1) {
            throw new CheckoutException('Meja terpakai tidak dapat dinonaktifkan.');
        }
    }

    /** @param array<string,string> $data */
    public function saveStaff(?int $id, array $data): void
    {
        $name = trim($data['name'] ?? '');
        $email = strtolower(trim($data['email'] ?? ''));
        $phone = trim($data['phone'] ?? '');
        $password = $data['password'] ?? '';
        if ($name === '' || mb_strlen($name) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new CheckoutException('Nama dan email Petugas belum valid.');
        }
        if ($id === null && strlen($password) < 8) {
            throw new CheckoutException('Password Petugas baru minimal 8 karakter.');
        }
        try {
            if ($id === null) {
                $statement = $this->pdo->prepare("INSERT INTO users(name,email,phone,password_hash,role,is_active) VALUES(:name,:email,:phone,:hash,'staff',1)");
                $statement->execute(['name' => $name, 'email' => $email, 'phone' => $phone ?: null, 'hash' => password_hash($password, PASSWORD_DEFAULT)]);
            } else {
                $sql = 'UPDATE users SET name=:name,email=:email,phone=:phone';
                $params = ['name' => $name, 'email' => $email, 'phone' => $phone ?: null, 'id' => $id];
                if ($password !== '') {
                    if (strlen($password) < 8) {
                        throw new CheckoutException('Password baru minimal 8 karakter.');
                    }
                    $sql .= ',password_hash=:hash';
                    $params['hash'] = password_hash($password, PASSWORD_DEFAULT);
                }
                $sql .= " WHERE id=:id AND role='staff'";
                $this->pdo->prepare($sql)->execute($params);
            }
        } catch (PDOException $exception) {
            throw new CheckoutException($exception->getCode() === '23000' ? 'Email sudah digunakan.' : 'Akun Petugas belum dapat disimpan.');
        }
    }

    public function toggleStaff(int $id): void
    {
        $statement = $this->pdo->prepare("UPDATE users SET is_active=1-is_active WHERE id=:id AND role='staff'");
        $statement->execute(['id' => $id]);
    }

    private function positiveId(string $value): int
    {
        if (!ctype_digit($value) || (int) $value < 1) {
            throw new CheckoutException('Referensi data tidak valid.');
        }

        return (int) $value;
    }
}
