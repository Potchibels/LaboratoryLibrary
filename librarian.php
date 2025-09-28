
<?php
session_start();
require_once 'database.php';


if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'librarian') {
    header("Location: login.php");
    exit();
}

$success_message = '';
$error_message = '';
$edit_id = $_GET['edit_id'] ?? null; 

if (isset($_GET['success'])) {
    $success_message = htmlspecialchars($_GET['success']);
}

if (isset($_POST['delete_book']) && isset($_POST['book_id'])) {
    $book_id = $conn->real_escape_string($_POST['book_id']);
    $sql_delete = "DELETE FROM books WHERE book_id = ?";
    if ($stmt = $conn->prepare($sql_delete)) {
        $stmt->bind_param("i", $book_id);
        if ($stmt->execute()) {
            $success_message = "Book ID $book_id successfully removed from the catalog.";
        } else {
            $error_message = "Error deleting book: " . $stmt->error;
        }
        $stmt->close();
    }
}

if (isset($_POST['update_book'])) {
    $book_id = $conn->real_escape_string($_POST['edit_book_id']);
    $title   = $conn->real_escape_string($_POST['edit_title']);
    $author  = $conn->real_escape_string($_POST['edit_author']);
    $quantity = (int)$_POST['edit_quantity'];
    
    $current_data = $conn->query("SELECT quantity, available_copies FROM books WHERE book_id = $book_id")->fetch_assoc();
    if ($current_data) {
        $quantity_diff = $quantity - $current_data['quantity'];
        $new_available_copies = $current_data['available_copies'] + $quantity_diff;
        if ($new_available_copies < 0) {
            $new_available_copies = 0; 
        }
    } else {
        $error_message = "Cannot update: Book ID not found.";
        $new_available_copies = $quantity; 
    }

    $sql_update = "UPDATE books SET title = ?, author = ?, quantity = ?, available_copies = ? WHERE book_id = ?";
    if ($stmt = $conn->prepare($sql_update)) {
        $stmt->bind_param("ssiii", $title, $author, $quantity, $new_available_copies, $book_id);
        if ($stmt->execute()) {
            $success_message = "Book ID $book_id details updated successfully (Title: $title).";
        } else {
            $error_message = "Error updating book: " . $stmt->error;
        }
        $stmt->close();
    }
    header("Location: librarian.php?success=" . urlencode($success_message));
    exit();
}

$books = $conn->query("SELECT * FROM books ORDER BY book_id ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Librarian Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f6fa;
            margin: 0;
            padding: 20px;
        }
        h1 {
            text-align: center;
            color: #2d3436;
        }
        .message {
            text-align: center;
            margin: 10px 0;
            padding: 10px;
            border-radius: 8px;
            font-weight: bold;
        }
        .success { background: #dff9fb; color: #0984e3; }
        .error { background: #fab1a0; color: #d63031; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        table th, table td {
            padding: 12px;
            border: 1px solid #dcdde1;
            text-align: center;
        }
        table th {
            background: #0984e3;
            color: white;
        }
        form {
            display: inline-block;
        }
        input[type="text"], input[type="number"] {
            padding: 5px;
            border: 1px solid #b2bec3;
            border-radius: 5px;
        }
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-edit { background: #74b9ff; color: white; }
        .btn-delete { background: #d63031; color: white; }
        .btn-update { background: #00b894; color: white; }
    </style>
</head>
<body>

    <h1>📚 Librarian Dashboard</h1>

    <?php if ($success_message): ?>
        <div class="message success"><?= $success_message; ?></div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="message error"><?= $error_message; ?></div>
    <?php endif; ?>

    <table>
        <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Author</th>
            <th>ISBN</th>
            <th>Quantity</th>
            <th>Available</th>
            <th>Actions</th>
        </tr>
        <?php while ($row = $books->fetch_assoc()): ?>
            <tr>
                <td><?= $row['book_id']; ?></td>
                <td>
                    <?php if ($edit_id == $row['book_id']): ?>
                        <form method="POST">
                            <input type="hidden" name="edit_book_id" value="<?= $row['book_id']; ?>">
                            <input type="text" name="edit_title" value="<?= htmlspecialchars($row['title']); ?>">
                    <?php else: ?>
                        <?= htmlspecialchars($row['title']); ?>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($edit_id == $row['book_id']): ?>
                        <input type="text" name="edit_author" value="<?= htmlspecialchars($row['author']); ?>">
                    <?php else: ?>
                        <?= htmlspecialchars($row['author']); ?>
                    <?php endif; ?>
                </td>
                <td><?= $row['isbn']; ?></td>
                <td>
                    <?php if ($edit_id == $row['book_id']): ?>
                        <input type="number" name="edit_quantity" value="<?= $row['quantity']; ?>">
                    <?php else: ?>
                        <?= $row['quantity']; ?>
                    <?php endif; ?>
                </td>
                <td><?= $row['available_copies']; ?></td>
                <td>
                    <?php if ($edit_id == $row['book_id']): ?>
                        <button type="submit" name="update_book" class="btn btn-update">Save</button>
                        </form>
                    <?php else: ?>
                        <a href="librarian.php?edit_id=<?= $row['book_id']; ?>" class="btn btn-edit">Edit</a>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="book_id" value="<?= $row['book_id']; ?>">
                            <button type="submit" name="delete_book" class="btn btn-delete" onclick="return confirm('Delete this book?')">Delete</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>

</body>
</html>