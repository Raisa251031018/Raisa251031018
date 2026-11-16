-- University Library Management System: full schema + default admin
-- Import into an EMPTY database (phpMyAdmin -> Import).
-- Local:  create a database named  library_db  first.
-- cPanel: create the database + user in "MySQL Databases", then import into it.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    username VARCHAR(60) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','librarian') NOT NULL DEFAULT 'librarian',
    phone VARCHAR(30) DEFAULT '',
    email VARCHAR(120) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(40) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    department VARCHAR(100) DEFAULT '',
    phone VARCHAR(30) DEFAULT '',
    email VARCHAR(120) DEFAULT '',
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(150) NOT NULL,
    isbn VARCHAR(40) DEFAULT '',
    category_id INT NULL,
    total_copies INT NOT NULL DEFAULT 1,
    available_copies INT NOT NULL DEFAULT 1,
    cover_image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX(title), INDEX(author), INDEX(isbn)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS issues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT NOT NULL,
    student_id INT NOT NULL,
    issued_by INT NULL,
    issue_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE NULL,
    fine INT NOT NULL DEFAULT 0,
    status ENUM('issued','returned') NOT NULL DEFAULT 'issued',
    INDEX(book_id), INDEX(student_id), INDEX(status), INDEX(due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Website settings (name, tagline) - editable from Admin -> Settings
CREATE TABLE IF NOT EXISTS settings (
    skey VARCHAR(60) NOT NULL PRIMARY KEY,
    svalue TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO settings (skey, svalue) VALUES
    ('site_name', 'University Library'),
    ('site_tagline', 'Search books, track issues, returns and fines.');

-- Default admin  ->  username: admin   password: admin123   (change it after first login!)
INSERT INTO users (name, username, password, role)
SELECT 'Administrator', 'admin', '$2y$10$8hvMW4erA3rC0hKPjBpwG.Duvd2tjVRb9.22gBqCvxJ4r2GoeWAFi', 'admin'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'admin');
