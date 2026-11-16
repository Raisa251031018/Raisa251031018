<?php
class ReportController
{
    public static function summary(array $d): void
    {
        need(is_staff());
        $T = today();
        $r = [];
        $r['titles']         = (int) q("SELECT COUNT(*) c FROM books")->fetch()['c'];
        $r['total_books']    = (int) q("SELECT COALESCE(SUM(total_copies),0) c FROM books")->fetch()['c'];
        $r['available']      = (int) q("SELECT COALESCE(SUM(available_copies),0) c FROM books")->fetch()['c'];
        $r['students']       = (int) q("SELECT COUNT(*) c FROM students")->fetch()['c'];
        $r['issued_now']     = (int) q("SELECT COUNT(*) c FROM issues WHERE status = 'issued'")->fetch()['c'];
        $r['total_issued']   = (int) q("SELECT COUNT(*) c FROM issues")->fetch()['c'];
        $r['returned']       = (int) q("SELECT COUNT(*) c FROM issues WHERE status = 'returned'")->fetch()['c'];
        $r['overdue']        = (int) q("SELECT COUNT(*) c FROM issues WHERE status = 'issued' AND due_date < ?", [$T])->fetch()['c'];
        $r['fine_collected'] = (int) q("SELECT COALESCE(SUM(fine),0) c FROM issues WHERE status = 'returned'")->fetch()['c'];
        $r['fine_pending']   = (int) q("SELECT COALESCE(SUM(DATEDIFF(?,due_date)),0) c FROM issues WHERE status='issued' AND due_date<?", [$T, $T])->fetch()['c'] * FINE_PER_DAY;
        $r['fine_rate']      = FINE_PER_DAY;
        out($r);
    }
}
