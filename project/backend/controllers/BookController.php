<?php
class BookController
{
    public static function index(array $d): void
    {
        need(current_role());
        $w = ['1=1'];
        $p = [];
        $s = trim($d['q'] ?? '');
        if ($s !== '') {
            $w[] = "(b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ?)";
            $like = "%$s%";
            array_push($p, $like, $like, $like);
        }
        if (!empty($d['cat'])) {
            $w[] = "b.category_id = ?";
            $p[] = $d['cat'];
        }
        if (!empty($d['avail'])) {
            $w[] = "b.available_copies > 0";
        }
        out(q(
            "SELECT b.*, c.name cat FROM books b LEFT JOIN categories c ON c.id = b.category_id
             WHERE " . implode(' AND ', $w) . " ORDER BY b.id DESC LIMIT 500",
            $p
        )->fetchAll());
    }

    public static function save(array $d): void
    {
        need(is_staff());
        $t   = trim($d['title'] ?? '');
        $au  = trim($d['author'] ?? '');
        $tot = max(1, (int) ($d['total_copies'] ?? 1));
        $cat = !empty($d['category_id']) ? (int) $d['category_id'] : null;

        if ($t === '' || $au === '') {
            out(['error' => 'Please enter the book title and author'], 422);
        }

        try {
            if (!empty($d['id'])) {
                $old = q("SELECT total_copies, available_copies, cover_image FROM books WHERE id = ?", [$d['id']])->fetch();
                if (!$old) {
                    out(['error' => 'Book not found'], 404);
                }
                $outOnLoan = $old['total_copies'] - $old['available_copies'];
                if ($tot < $outOnLoan) {
                    out(['error' => "Total copies cannot be less than the $outOnLoan copies currently issued"], 422);
                }

                $newFile = isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] !== UPLOAD_ERR_NO_FILE;
                $cover = handle_cover_upload($_FILES['cover_image'] ?? null, $old['cover_image']);
                if (!empty($d['remove_cover']) && !$newFile) {
                    delete_cover($cover);
                    $cover = null;
                }
                q(
                    "UPDATE books SET title=?, author=?, isbn=?, category_id=?, total_copies=?, available_copies=?, cover_image=? WHERE id=?",
                    [$t, $au, trim($d['isbn'] ?? ''), $cat, $tot, max(0, $tot - $outOnLoan), $cover, $d['id']]
                );
            } else {
                $cover = handle_cover_upload($_FILES['cover_image'] ?? null, null);
                q(
                    "INSERT INTO books(title, author, isbn, category_id, total_copies, available_copies, cover_image) VALUES (?,?,?,?,?,?,?)",
                    [$t, $au, trim($d['isbn'] ?? ''), $cat, $tot, $tot, $cover]
                );
            }
        } catch (RuntimeException $e) {
            out(['error' => $e->getMessage()], 422);
        }
        out(['ok' => 1]);
    }

    public static function delete(array $d): void
    {
        need(is_staff());
        if (q("SELECT COUNT(*) c FROM issues WHERE book_id = ? AND status = 'issued'", [$d['id']])->fetch()['c']) {
            out(['error' => 'This book is currently issued and cannot be deleted'], 422);
        }
        $b = q("SELECT cover_image FROM books WHERE id = ?", [$d['id']])->fetch();
        delete_cover($b['cover_image'] ?? null);
        q("DELETE FROM issues WHERE book_id = ?", [$d['id']]);
        q("DELETE FROM books WHERE id = ?", [$d['id']]);
        out(['ok' => 1]);
    }
}
