<?php
// Include database connection
include('db.php');

// Handle registration
if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email'];

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Check if user already exists
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $registerStatus = "Username sudah terdaftar.";
    } else {
        // Insert new user into database
        $sql = "INSERT INTO users (username, password, email) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $username, $hashedPassword, $email);

        if ($stmt->execute()) {
            $registerStatus = "Registrasi berhasil. Silakan login.";
        } else {
            $registerStatus = "Terjadi kesalahan. Coba lagi.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>clouDrive</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        h1 {
            text-align: center;
            margin-top: 50px;
            color: #333;
        }

        .form {
            width: 600px;
            padding: 20px;
            background: white;
            margin: 0 auto;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .form input[type="text"],
        .form input[type="email"],
        .form input[type="password"] {
            width: 95%;
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

        p {
            text-align: center;
            color: #333;
        }

        .status {
            text-align: center;
            font-size: 14px;
            margin-top: 20px;
        }

        .status.success {
            color: green;
        }

        .status.error {
            color: red;
        }

    </style>
</head>
<body>
    <h1>Registrasi Pengguna Baru</h1>

    <div class="form">
        <form action="register.php" method="POST">
            <label for="username">Username:</label>
            <input type="text" name="username" required>

            <label for="email">Email:</label>
            <input type="email" name="email" required>

            <label for="password">Password:</label>
            <input type="password" name="password" required>

            <button type="submit" name="register">Registrasi</button>
        </form>

        <?php
        if (isset($registerStatus)) {
            $statusClass = (strpos($registerStatus, 'berhasil') !== false) ? 'success' : 'error';
            echo "<div class='status $statusClass'>$registerStatus</div>";
        }
        ?>

        <p>Sudah punya akun? <a href="login.php">Login</a></p>
    </div>
</body>
</html>
