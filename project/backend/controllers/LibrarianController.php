<?php
class LibrarianController
{
    public static function index(array $d): void
    {
        need(current_role() === 'admin');
        out(q("SELECT id, name, username, phone, email, role FROM users ORDER BY id")->fetchAll());
    }

    public static function save(array $d): void
    {
        need(current_role() === 'admin');
        $n = trim($d['name'] ?? '');
        $u = trim($d['username'] ?? '');
        if ($n === '' || $u === '') {
            out(['error' => 'Please enter name and username'], 422);
        }
        if (!empty($d['id'])) {
            q("UPDATE users SET name=?, username=?, phone=?, email=? WHERE id=?", [$n, $u, trim($d['phone'] ?? ''), trim($d['email'] ?? ''), $d['id']]);
            if (!empty($d['password'])) {
                q("UPDATE users SET password = ? WHERE id = ?", [password_hash($d['password'], PASSWORD_DEFAULT), $d['id']]);
            }
        } else {
            if (empty($d['password'])) {
                out(['error' => 'Please enter a password'], 422);
            }
            q(
                "INSERT INTO users(name, username, password, role, phone, email) VALUES (?,?,?,'librarian',?,?)",
                [$n, $u, password_hash($d['password'], PASSWORD_DEFAULT), trim($d['phone'] ?? ''), trim($d['email'] ?? '')]
            );
        }
        out(['ok' => 1]);
    }

    public static function delete(array $d): void
    {
        need(current_role() === 'admin');
        if ((int) $d['id'] === (int) $_SESSION['uid']) {
            out(['error' => 'You cannot delete your own account'], 422);
        }
        q("DELETE FROM users WHERE id = ?", [$d['id']]);
        out(['ok' => 1]);
    }
}
