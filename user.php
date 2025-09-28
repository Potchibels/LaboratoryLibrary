<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

$username = htmlspecialchars($_SESSION['username']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Landing Page</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f6fa;
            margin: 0;
            padding: 0;
            text-align: center;
        }
        header {
            background: #2c3e50;
            color: white;
            padding: 15px;
        }
        nav {
            margin: 30px;
        }
        nav a button {
            background: #3498db;
            border: none;
            color: white;
            padding: 12px 20px;
            margin: 8px;
            font-size: 15px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s;
        }
        nav a button:hover {
            background: #2980b9;
        }
        h1 {
            color: #2c3e50;
        }
        table {
            margin: 20px auto;
            border-collapse: collapse;
            width: 90%;
            background: white;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
        }
        th {
            background: #3498db;
            color: white;
        }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        button.borrow-btn {
            background: #27ae60;
            border: none;
            color: white;
            padding: 6px 12px;
            border-radius: 5px;
            cursor: pointer;
        }
        button.borrow-btn:hover {
            background: #1e8449;
        }
        button.return-btn {
            background: #e67e22;
            border: none;
            color: white;
            padding: 6px 12px;
            border-radius: 5px;
            cursor: pointer;
        }
        button.return-btn:hover {
            background: #ca6f1e;
        }
        p {
            font-weight: bold;
            text-align: center;
            color: #c0392b;
        }
    </style>
</head>
<body>
<header>
    <h2>Welcome, <?php echo $username; ?>!</h2>
</header>

<nav>
    <a href="button.php"><button>Button 1</button></a>
    <a href="button.php"><button>Button 2</button></a>
    <a href="borrow.php"><button>Go to Borrow Books</button></a>
    <a href="login.php?logout=1p"><button>Logout</button></a>
</nav>
</body>
</html>


<?php
require_once 'database.php';

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['book_id'])) {
    $book_id = intval($_POST['book_id']);

    $sql_update = "UPDATE books 
                SET available_copies = available_copies - 1 
                WHERE book_id = $book_id AND available_copies > 0";

    if ($conn->query($sql_update) === TRUE) {
        $sql_insert = "INSERT INTO borrowed_books (user_id, book_id) 
                    VALUES ($user_id, $book_id)";
        if ($conn->query($sql_insert) === TRUE) {
            $message = "Book borrowed successfully!";
        } else {
            $message = "Error recording borrow: " . $conn->error;
        }
    } else {
        $message = "Error borrowing book: " . $conn->error;
    }
}

$sql = "SELECT book_id, title, author, publication_year, available_copies 
        FROM books 
        WHERE is_Available = TRUE AND available_copies > 0";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Borrow Books</title>
</head>
<body>

<header>
    <h2>Borrow Books</h2>
</header>

<nav>
    <a href="button.php"><button>Button 1</button></a>
    <a href="button.php"><button>Button 2</button></a>
    <a href="breturn.php"><button>Return Books</button></a>
    <a href="login.php?logout=1"><button>Logout</button></a>
</nav>

<h1>Available Books</h1>

<?php if ($result && $result->num_rows > 0): ?>
    <table>
        <tr>
            <th>Title</th>
            <th>Author</th>
            <th>Year</th>
            <th>Available Copies</th>
            <th>Action</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['title']); ?></td>
                <td><?php echo htmlspecialchars($row['author']); ?></td>
                <td><?php echo htmlspecialchars($row['publication_year']); ?></td>
                <td><?php echo $row['available_copies']; ?></td>
                <td>
                    <form method="post" style="margin:0;">
                        <input type="hidden" name="book_id" value="<?php echo $row['book_id']; ?>">
                        <button type="submit" class="borrow-btn">Borrow</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
<?php else: ?>
    <p>No books available to borrow.</p>
<?php endif; ?>

<?php if (isset($message)) echo "<p>$message</p>"; ?>

</body>
</html>


<?php
require_once 'database.php';

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['borrow_id'], $_POST['book_id'])) {
    $borrow_id = intval($_POST['borrow_id']);
    $book_id   = intval($_POST['book_id']);

    $conn->query("UPDATE borrowed_books SET return_date = NOW() WHERE borrow_id = $borrow_id AND user_id = $user_id");
    $conn->query("UPDATE books SET available_copies = available_copies + 1 WHERE book_id = $book_id");

    $message = "Book returned successfully!";
}

$sql = "SELECT bb.borrow_id, b.book_id, b.title, b.author, b.publication_year
        FROM borrowed_books bb
        JOIN books b ON bb.book_id = b.book_id
        WHERE bb.user_id = $user_id AND bb.return_date IS NULL";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Return Books</title>
</head>
<body>

<header>
    <h2>Return Books</h2>
</header>

<nav>
    <a href="button.php"><button>Button 1</button></a>
    <a href="button.php"><button>Button 2</button></a>
    <a href="borrow.php"><button>Go to Borrow Books</button></a>
    <a href="login.php?logout=1p"><button>Logout</button></a>
</nav>

<h1>Books You Borrowed</h1>

<?php if ($result && $result->num_rows > 0): ?>
    <table>
        <tr>
            <th>Title</th>
            <th>Author</th>
            <th>Year</th>
            <th>Action</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['title']); ?></td>
                <td><?php echo htmlspecialchars($row['author']); ?></td>
                <td><?php echo htmlspecialchars($row['publication_year']); ?></td>
                <td>
                    <form method="post" style="margin:0;">
                        <input type="hidden" name="borrow_id" value="<?php echo $row['borrow_id']; ?>">
                        <input type="hidden" name="book_id" value="<?php echo $row['book_id']; ?>">
                        <button type="submit" class="return-btn">Return</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
<?php else: ?>
    <p>You have not borrowed any books.</p>
<?php endif; ?>

<?php if (isset($message)) echo "<p>$message</p>"; ?>

</body>
</html>