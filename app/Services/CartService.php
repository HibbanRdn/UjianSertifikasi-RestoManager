<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\SessionStore;
use App\Repositories\MenuItemRepository;

final class CartService
{
    private const CART_KEY = 'customer.cart';
    private const TABLE_KEY = 'customer.draft_table';
    private const LEGACY_TABLE_KEY = 'customer.selected_table_id';
    private const PENDING_KEY = 'customer.pending_add';

    public function __construct(
        private readonly SessionStore $session,
        private readonly MenuItemRepository $menuItems
    ) {
    }

    public function rememberPending(int $menuItemId, int $quantity = 1): void
    {
        $this->session->put(self::PENDING_KEY, [
            'menu_item_id' => $menuItemId,
            'quantity' => $this->normalizeQuantity($quantity),
        ]);
    }

    public function hasPending(): bool
    {
        return is_array($this->session->get(self::PENDING_KEY));
    }

    public function consumePending(int $userId): bool
    {
        $pending = $this->session->get(self::PENDING_KEY);
        if (!is_array($pending)) {
            return false;
        }

        $menuItemId = (int) ($pending['menu_item_id'] ?? 0);
        $quantity = $this->normalizeQuantity((int) ($pending['quantity'] ?? 1));
        $item = $this->menuItems->findVisibleById($menuItemId);
        $this->session->forget(self::PENDING_KEY);

        if ($item === null || (int) $item['is_available'] !== 1) {
            return false;
        }

        $this->add($userId, $menuItemId, $quantity);

        return true;
    }

    public function add(int $userId, int $menuItemId, int $quantity = 1): void
    {
        $cart = $this->ownedCart($userId);
        $current = (int) ($cart['items'][$menuItemId] ?? 0);
        $cart['items'][$menuItemId] = min(99, $current + $this->normalizeQuantity($quantity));
        $cart['revision'] = bin2hex(random_bytes(12));
        $this->session->put(self::CART_KEY, $cart);
        $this->session->forget('customer.checkout_draft');
    }

    public function update(int $userId, int $menuItemId, int $quantity): void
    {
        $cart = $this->ownedCart($userId);
        if (!array_key_exists($menuItemId, $cart['items'])) {
            return;
        }
        $cart['items'][$menuItemId] = $this->normalizeQuantity($quantity);
        $cart['revision'] = bin2hex(random_bytes(12));
        $this->session->put(self::CART_KEY, $cart);
        $this->session->forget('customer.checkout_draft');
    }

    public function remove(int $userId, int $menuItemId): void
    {
        $cart = $this->ownedCart($userId);
        unset($cart['items'][$menuItemId]);
        $cart['revision'] = bin2hex(random_bytes(12));
        $this->session->put(self::CART_KEY, $cart);
        $this->session->forget('customer.checkout_draft');
    }

    public function clear(int $userId): void
    {
        $this->session->put(self::CART_KEY, ['user_id' => $userId, 'items' => [], 'revision' => bin2hex(random_bytes(12))]);
        $this->session->forget('customer.checkout_draft');
    }

    /** @return array{items:list<array<string,mixed>>,total_idr:int,revision:string} */
    public function details(int $userId): array
    {
        $cart = $this->ownedCart($userId);
        $itemsById = [];
        foreach ($this->menuItems->findVisibleByIds(array_map('intval', array_keys($cart['items']))) as $item) {
            $itemsById[(int) $item['id']] = $item;
        }

        $details = [];
        $total = 0;
        foreach ($cart['items'] as $menuItemId => $quantity) {
            $item = $itemsById[(int) $menuItemId] ?? null;
            if ($item === null) {
                continue;
            }
            $lineTotal = (int) $item['price_idr'] * (int) $quantity;
            $details[] = $item + ['quantity' => (int) $quantity, 'line_total_idr' => $lineTotal];
            $total += $lineTotal;
        }

        return ['items' => $details, 'total_idr' => $total, 'revision' => (string) $cart['revision']];
    }

    public function setDraftTable(int $userId, int $tableId): void
    {
        $this->session->put(self::TABLE_KEY, ['user_id' => $userId, 'table_id' => $tableId]);
        $this->session->forget(self::LEGACY_TABLE_KEY);
        $this->session->forget('customer.checkout_draft');
    }

    public function draftTableId(int $userId): ?int
    {
        $draft = $this->session->get(self::TABLE_KEY);
        if (!is_array($draft) || (int) ($draft['user_id'] ?? 0) !== $userId) {
            return null;
        }

        $id = (int) ($draft['table_id'] ?? 0);

        return $id > 0 ? $id : null;
    }

    public function clearDraftTable(): void
    {
        $this->session->forget(self::TABLE_KEY);
        $this->session->forget(self::LEGACY_TABLE_KEY);
        $this->session->forget('customer.checkout_draft');
    }

    /** @return array{user_id:int,items:array<int,int>,revision:string} */
    private function ownedCart(int $userId): array
    {
        $cart = $this->session->get(self::CART_KEY);
        if (!is_array($cart) || (int) ($cart['user_id'] ?? 0) !== $userId || !is_array($cart['items'] ?? null)) {
            return ['user_id' => $userId, 'items' => [], 'revision' => bin2hex(random_bytes(12))];
        }

        return $cart;
    }

    private function normalizeQuantity(int $quantity): int
    {
        return max(1, min(99, $quantity));
    }
}
