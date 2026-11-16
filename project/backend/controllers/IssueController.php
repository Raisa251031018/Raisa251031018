<?php
class IssueController
{
    public static function issue(array $d): void
    {
        need(is_staff());
        $T   = today();
        $sid = (int) ($d['student_id'] ?? 0);
        $bid = (int) ($d['book_id'] ?? 0);
        $due = trim((string) ($d['due_date'] ?? ''));
        if ($due === '') {
            $due = date('Y-m-d', strtotime('+' . LOAN_DAYS . ' days'));
        }
        $dt = DateTime::createFromFormat('Y-m-d', $due);
        if (!$dt || $dt->format('Y-m-d') !== $due) {
            out(['error' => 'Please choose a valid due date'], 422);
        }
        if ($due < $T) {
            out(['error' => 'Due date cannot be in the past'], 422);
        }
        if (!q("SELECT id FROM students WHERE id = ?", [$sid])->fetch()) {
            out(['error' => 'Student not found'], 422);
        }
        db()->beginTransaction();
        $b = q("SELECT available_copies FROM books WHERE id = ? FOR UPDATE", [$bid])->fetch();
        if (!$b || $b['available_copies'] < 1) {
            db()->rollBack();
            out(['error' => 'This book is not available right now'], 422);
        }
        if (q("SELECT COUNT(*) c FROM issues WHERE book_id = ? AND student_id = ? AND status = 'issued'", [$bid, $sid])->fetch()['c']) {
            db()->rollBack();
            out(['error' => 'This student already has this book'], 422);
        }
        q("INSERT INTO issues(book_id, student_id, issued_by, issue_date, due_date) VALUES (?,?,?,?,?)", [$bid, $sid, $_SESSION['uid'], $T, $due]);
        q("UPDATE books SET available_copies = available_copies - 1 WHERE id = ?", [$bid]);
        db()->commit();
        out(['ok' => 1]);
    }

    public static function returnBook(array $d): void
    {
        need(is_staff());
        $T = today();
        db()->beginTransaction();
        $i = q("SELECT * FROM issues WHERE id = ? AND status = 'issued' FOR UPDATE", [$d['id']])->fetch();
        if (!$i) {
            db()->rollBack();
            out(['error' => 'Issue record not found'], 404);
        }
        $late = max(0, (int) floor((strtotime($T) - strtotime($i['due_date'])) / 86400));
        $fine = $late * FINE_PER_DAY;
        q("UPDATE issues SET status = 'returned', return_date = ?, fine = ? WHERE id = ?", [$T, $fine, $i['id']]);
        q("UPDATE books SET available_copies = LEAST(total_copies, available_copies + 1) WHERE id = ?", [$i['book_id']]);
        db()->commit();
        out(['ok' => 1, 'fine' => $fine, 'late' => $late]);
    }

    /** All issue records (staff). Filter: issued | returned | overdue */
    public static function index(array $d): void
    {
        need(is_staff());
        self::listing($d, null);
    }

    /** The logged-in student's own history. */
    public static function myHistory(array $d): void
    {
        need(current_role() === 'student');
        self::listing($d, (int) $_SESSION['uid']);
    }

    private static function listing(array $d, ?int $studentId): void
    {
        $T = today();
        $w = ['1=1'];
        $p = [];
        $f = $d['filter'] ?? '';
        if ($studentId !== null) {
            $w[] = "i.student_id = ?";
            $p[] = $studentId;
        }
        if ($f === 'issued')   $w[] = "i.status = 'issued'";
        if ($f === 'returned') $w[] = "i.status = 'returned'";
        if ($f === 'overdue') {
            $w[] = "i.status = 'issued' AND i.due_date < ?";
            $p[] = $T;
        }
        out(q(
            "SELECT i.id, i.issue_date, i.due_date, i.return_date, i.status, b.title, s.name sname, s.student_id sid,
                    IF(i.status='issued', GREATEST(0,DATEDIFF(?,i.due_date)), GREATEST(0,DATEDIFF(i.return_date,i.due_date))) late,
                    IF(i.status='issued', GREATEST(0,DATEDIFF(?,i.due_date))*?, i.fine) fine
             FROM issues i JOIN books b ON b.id = i.book_id JOIN students s ON s.id = i.student_id
             WHERE " . implode(' AND ', $w) . " ORDER BY i.id DESC LIMIT 500",
            array_merge([$T, $T, FINE_PER_DAY], $p)
        )->fetchAll());
    }
}
