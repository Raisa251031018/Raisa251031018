<?php
class StudentController
{
    public static function index(array $d): void
    {
        need(is_staff());
        $like = '%' . trim($d['q'] ?? '') . '%';
        out(q(
            "SELECT id, student_id, name, department, phone, email FROM students
             WHERE name LIKE ? OR student_id LIKE ? OR department LIKE ? ORDER BY id DESC LIMIT 500",
            [$like, $like, $like]
        )->fetchAll());
    }

    public static function save(array $d): void
    {
        need(is_staff());
        $sid = trim($d['student_id'] ?? '');
        $n   = trim($d['name'] ?? '');
        if ($sid === '' || $n === '') {
            out(['error' => 'Please enter Student ID and name'], 422);
        }
        if (!empty($d['id'])) {
            q(
                "UPDATE students SET student_id=?, name=?, department=?, phone=?, email=? WHERE id=?",
                [$sid, $n, trim($d['department'] ?? ''), trim($d['phone'] ?? ''), trim($d['email'] ?? ''), $d['id']]
            );
            if (!empty($d['password'])) {
                q("UPDATE students SET password = ? WHERE id = ?", [password_hash($d['password'], PASSWORD_DEFAULT), $d['id']]);
            }
        } else {
            $pw = !empty($d['password']) ? $d['password'] : $sid; // blank = password is the Student ID
            q(
                "INSERT INTO students(student_id, name, department, phone, email, password) VALUES (?,?,?,?,?,?)",
                [$sid, $n, trim($d['department'] ?? ''), trim($d['phone'] ?? ''), trim($d['email'] ?? ''), password_hash($pw, PASSWORD_DEFAULT)]
            );
        }
        out(['ok' => 1]);
    }

    public static function delete(array $d): void
    {
        need(is_staff());
        if (q("SELECT COUNT(*) c FROM issues WHERE student_id = ? AND status = 'issued'", [$d['id']])->fetch()['c']) {
            out(['error' => 'This student still has books to return and cannot be deleted'], 422);
        }
        q("DELETE FROM issues WHERE student_id = ?", [$d['id']]);
        q("DELETE FROM students WHERE id = ?", [$d['id']]);
        out(['ok' => 1]);
    }
}
