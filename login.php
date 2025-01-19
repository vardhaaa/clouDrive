<?php
// Include database connection
include('db.php');

// Start session
session_start();

// Handle login
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check if username exists
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            // Redirect to index.php after successful login
            header("Location: index.php");
            exit(); // Terminate the script after the redirection
        } else {
            $loginStatus = "Password salah.";
        }
    } else {
        $loginStatus = "Username tidak ditemukan.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - clouDrive</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        h1 {
            text-align: center;
            margin-top: 50px;
            color: #333;
        }

        .form {
            width: 300px;
            padding: 20px;
            background-color: white;
            margin: 0 auto;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .form input[type="text"],
        .form input[type="password"] {
            width: 95%; //lebar si box
            height: 15px;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }

        .form button {
            width: 100%;
            padding: 12px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        .form button:hover {
            background-color: #0056b3;
        }

        .status {
            text-align: center;
            font-size: 14px;
            margin-top: 20px;
            color: red;
        }

        p {
            text-align: center;
            color: #333;
        }
    </style>
</head>
<body>
    <h1>Login ke clouDrive</h1>

    <div class="form">
        <form action="login.php" method="POST">
            <label for="username">Username:</label>
            <input type="text" name="username" required>

            <label for="password">Password:</label>
            <input type="password" name="password" required>

            <button type="submit" name="login">Login</button>
        </form>

        <?php
        if (isset($loginStatus)) {
            echo "<div class='status'>$loginStatus</div>";
        }
        ?>

        <p>Belum punya akun? <a href="register.php">Daftar</a></p>
    </div>
</body>
</html>
