<?php
class AuthController
{
    public static function login(array $d): void
    {
        if (($_SESSION['fails'] ?? 0) >= 8) {
            if (time() - ($_SESSION['fail_t'] ?? 0) < 300) {
                out(['error' => 'Too many attempts. Please wait 5 minutes.'], 429);
            }
            $_SESSION['fails'] = 0; // lock period is over: start counting again
        }
        $type = $d['type'] ?? 'staff';
        $u = trim($d['username'] ?? '');
        $p = $d['password'] ?? '';

        if ($type === 'staff') {
            $r = q("SELECT * FROM users WHERE username = ?", [$u])->fetch();
            if (!$r || !password_verify($p, $r['password'])) {
                self::fail('Invalid username or password');
            }
            $_SESSION = ['uid' => $r['id'], 'role' => $r['role'], 'name' => $r['name']];
        } else {
            $r = q("SELECT * FROM students WHERE student_id = ?", [$u])->fetch();
            if (!$r || !password_verify($p, $r['password'])) {
                self::fail('Invalid Student ID or password');
            }
            $_SESSION = ['uid' => $r['id'], 'role' => 'student', 'name' => $r['name']];
        }
        session_regenerate_id(true);
        out(['role' => $_SESSION['role'], 'name' => $_SESSION['name']]);
    }

    public static function me(array $d): void
    {
        out(current_role() ? ['role' => current_role(), 'name' => $_SESSION['name']] : ['role' => null]);
    }

    public static function logout(array $d): void
    {
        $_SESSION = [];
        session_destroy();
        out(['ok' => 1]);
    }

    /** Any logged-in user (admin / librarian / student) changes their own password. */
    public static function changePassword(array $d): void
    {
        need(current_role());
        $cur  = (string) ($d['current_password'] ?? '');
        $new  = (string) ($d['new_password'] ?? '');
        $conf = (string) ($d['confirm_password'] ?? '');

        if ($new !== $conf) {
            out(['error' => 'New password and confirmation do not match'], 422);
        }
        if (strlen($new) < 6) {
            out(['error' => 'New password must be at least 6 characters'], 422);
        }
        $table = current_role() === 'student' ? 'students' : 'users';
        $r = q("SELECT password FROM $table WHERE id = ?", [$_SESSION['uid']])->fetch();
        if (!$r || !password_verify($cur, $r['password'])) {
            out(['error' => 'Current password is incorrect'], 422);
        }
        if ($cur === $new) {
            out(['error' => 'New password must be different from the current one'], 422);
        }
        q("UPDATE $table SET password = ? WHERE id = ?", [password_hash($new, PASSWORD_DEFAULT), $_SESSION['uid']]);
        out(['ok' => 1]);
    }

    private static function fail(string $msg): void
    {
        $_SESSION['fails'] = ($_SESSION['fails'] ?? 0) + 1;
        $_SESSION['fail_t'] = time();
        out(['error' => $msg], 401);
    }
}
