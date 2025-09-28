<?php
$servername = "db";          
$username   = "root";
$password   = "rootpassword";
$dbname     = "lms_db";


$conn = new mysqli($servername, $username, $password);
if ($conn->connect_error) {
    die("Database Connection failed: " . $conn->connect_error);
}


$sql_create_db = "CREATE DATABASE IF NOT EXISTS $dbname";
if (!$conn->query($sql_create_db)) {
    die("Error creating database: " . $conn->error);
}


$conn->select_db($dbname);
$sql_create_users_table = "
CREATE TABLE IF NOT EXISTS users (
    user_id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL, 
    role ENUM('librarian', 'user') NOT NULL, 
    reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
";
$conn->query($sql_create_users_table);


$sql_create_borrowed_table = "
CREATE TABLE IF NOT EXISTS borrowed_books (
    borrow_id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT(6) UNSIGNED NOT NULL,
    book_id INT(6) UNSIGNED NOT NULL,
    borrow_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    return_date TIMESTAMP NULL,
    returned BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE
) ENGINE=InnoDB;
";
$conn->query($sql_create_borrowed_table);



// Insert initial books
$books_to_insert = [
    ["Half a Soul", "Olivia Atwater", "978-0358253907", 2020, 5],
    ["The Ballad of Never After", "Stephanie Garber", "978-1250266023", 2022, 3],
    ["Cinder", "Marissa Meyer", "978-1250007817", 2012, 7],
    ["If You Could See the Sun", "Ann Liang", "978-1335425624", 2022, 4],
    ["Divine Rivals", "Rebecca Ross", "978-1250803302", 2023, 6],
];

foreach ($books_to_insert as $book) {
    [$title, $author, $isbn, $year, $quantity] = $book;

    $title  = $conn->real_escape_string($title);
    $author = $conn->real_escape_string($author);
    $isbn   = $conn->real_escape_string($isbn);

    $sql_insert_book = "
        INSERT INTO books (title, author, isbn, publication_year, quantity, available_copies)
        SELECT * FROM (
            SELECT 
                '$title' AS title,
                '$author' AS author,
                '$isbn' AS isbn,
                '$year' AS publication_year,
                $quantity AS quantity,
                $quantity AS available_copies
        ) AS tmp
        WHERE NOT EXISTS (
            SELECT isbn FROM books WHERE isbn = '$isbn'
        ) LIMIT 1;
    ";
    $conn->query($sql_insert_book);
}

// Insert initial users
$librarian_password = password_hash("admin123", PASSWORD_DEFAULT);
$user_password      = password_hash("user123", PASSWORD_DEFAULT);

$initial_users = [
    ["librarian_user", $librarian_password, 'librarian'],
    ["standard_user", $user_password, 'user'],
];

foreach ($initial_users as $user) {
    [$username, $hashed_password, $role] = $user;

    $username = $conn->real_escape_string($username);

    $sql_insert_user = "
        INSERT INTO users (username, password_hash, role)
        SELECT * FROM (
            SELECT 
                '$username' AS username,
                '$hashed_password' AS password_hash,
                '$role' AS role
        ) AS tmp
        WHERE NOT EXISTS (
            SELECT username FROM users WHERE username = '$username'
        ) LIMIT 1;
    ";
    $conn->query($sql_insert_user);
}

?>

