<?php
/**
 * Website settings (name, tagline) stored in the `settings` table.
 * The table is created automatically if it does not exist yet, so older
 * installs keep working without re-importing database.sql.
 */
class SettingsController
{
    const DEFAULTS = [
        'site_name'    => 'University Library',
        'site_tagline' => 'Search books, track issues, returns and fines.',
    ];

    /** Public (no login): name + tagline shown on every page. Never fails. */
    public static function site(array $d): void
    {
        out(self::current());
    }

    /** Admin only: change the website name / tagline. */
    public static function save(array $d): void
    {
        need(current_role() === 'admin');

        $name = trim((string) preg_replace('/\s+/u', ' ', (string) ($d['site_name'] ?? '')));
        $tag  = trim((string) ($d['site_tagline'] ?? ''));

        if ($name === '') {
            out(['error' => 'Please enter the website name'], 422);
        }
        if (self::len($name) > 80) {
            out(['error' => 'Website name is too long (maximum 80 characters)'], 422);
        }
        if (self::len($tag) > 160) {
            out(['error' => 'Tagline is too long (maximum 160 characters)'], 422);
        }

        self::ensureTable();
        foreach (['site_name' => $name, 'site_tagline' => $tag] as $k => $v) {
            q(
                "INSERT INTO settings (skey, svalue) VALUES (?, ?) ON DUPLICATE KEY UPDATE svalue = ?",
                [$k, $v, $v]
            );
        }
        out(['ok' => 1] + self::current());
    }

    /** Current values, falling back to defaults (also when the table does not exist yet). */
    public static function current(): array
    {
        $v = self::DEFAULTS;
        try {
            foreach (q("SELECT skey, svalue FROM settings")->fetchAll() as $r) {
                if (array_key_exists($r['skey'], $v)) {
                    $v[$r['skey']] = $r['svalue'];
                }
            }
        } catch (PDOException $e) {
            // table missing or database unreachable -> defaults
        }
        return ['name' => $v['site_name'], 'tagline' => $v['site_tagline']];
    }

    private static function ensureTable(): void
    {
        q("CREATE TABLE IF NOT EXISTS settings (
              skey   VARCHAR(60) NOT NULL PRIMARY KEY,
              svalue TEXT NOT NULL
           ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private static function len(string $s): int
    {
        return function_exists('mb_strlen') ? mb_strlen($s) : strlen($s);
    }
}
