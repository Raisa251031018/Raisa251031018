<?php
class CategoryController
{
    public static function index(array $d): void
    {
        need(current_role());
        out(q("SELECT c.*, (SELECT COUNT(*) FROM books b WHERE b.category_id = c.id) cnt
               FROM categories c ORDER BY name")->fetchAll());
    }

    public static function save(array $d): void
    {
        need(is_staff());
        $n = trim($d['name'] ?? '');
        if ($n === '') {
            out(['error' => 'Please enter a category name'], 422);
        }
        if (!empty($d['id'])) {
            q("UPDATE categories SET name = ? WHERE id = ?", [$n, $d['id']]);
        } else {
            q("INSERT INTO categories(name) VALUES (?)", [$n]);
        }
        out(['ok' => 1]);
    }

    public static function delete(array $d): void
    {
        need(is_staff());
        q("UPDATE books SET category_id = NULL WHERE category_id = ?", [$d['id']]);
        q("DELETE FROM categories WHERE id = ?", [$d['id']]);
        out(['ok' => 1]);
    }
}
