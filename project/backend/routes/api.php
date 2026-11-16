<?php
/** action  =>  [Controller, method] */
return [
    // Auth
    'login'        => ['AuthController', 'login'],
    'me'           => ['AuthController', 'me'],
    'logout'       => ['AuthController', 'logout'],
    'password_save'=> ['AuthController', 'changePassword'],

    // Website settings (name / tagline): 'site' is public, saving is admin only
    'site'         => ['SettingsController', 'site'],
    'settings_save'=> ['SettingsController', 'save'],

    // Categories
    'cats'         => ['CategoryController', 'index'],
    'cat_save'     => ['CategoryController', 'save'],
    'cat_del'      => ['CategoryController', 'delete'],

    // Books
    'books'        => ['BookController', 'index'],
    'book_save'    => ['BookController', 'save'],
    'book_del'     => ['BookController', 'delete'],

    // Students
    'students'     => ['StudentController', 'index'],
    'student_save' => ['StudentController', 'save'],
    'student_del'  => ['StudentController', 'delete'],

    // Librarians (admin only)
    'libs'         => ['LibrarianController', 'index'],
    'lib_save'     => ['LibrarianController', 'save'],
    'lib_del'      => ['LibrarianController', 'delete'],

    // Issue / return / history
    'issue'        => ['IssueController', 'issue'],
    'return'       => ['IssueController', 'returnBook'],
    'issues'       => ['IssueController', 'index'],
    'my_history'   => ['IssueController', 'myHistory'],

    // Dashboard numbers
    'reports'      => ['ReportController', 'summary'],
];
