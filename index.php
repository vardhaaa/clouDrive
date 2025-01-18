<?php
// Include the database connection
include('db.php');

// Initialize $sortBy and $order with default values
$sortBy = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'upload_time'; // Default sort by upload_time
$order = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'asc' : 'desc'; // Default order descending

// Check if form is submitted for uploading
if (isset($_POST['submit'])) {
    $fileName = $_FILES['file']['name'];  // Use the original file name
    $fileTmpName = $_FILES['file']['tmp_name'];
    $fileSize = $_FILES['file']['size'];
    $fileError = $_FILES['file']['error'];
    $fileType = $_FILES['file']['type'];

    // File extensions allowed
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'docx'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    // Check if file type is allowed
    if (!in_array($fileExtension, $allowedExtensions)) {
        $uploadStatus = "Tipe file tidak diizinkan. Hanya file JPG, PNG, PDF, dan DOCX yang dapat diupload.";
    } else {
        // Check if there is an error
        if ($fileError === 0) {
            // Use the original file name for upload
            $fileDestination = 'uploads/' . $fileName;

            // Ensure uploads folder exists
            if (!is_dir('uploads')) {
                mkdir('uploads', 0777, true);
            }

            // Move the file to the destination
            if (move_uploaded_file($fileTmpName, $fileDestination)) {
                // Insert file info into the database
                $sql = $conn->prepare("INSERT INTO files (file_name, file_type, file_size) VALUES (?, ?, ?)");
                $sql->bind_param("ssi", $fileName, $fileType, $fileSize);

                if ($sql->execute()) {
                    $uploadStatus = "File berhasil diupload.";
                } else {
                    $uploadStatus = "Error: " . $conn->error;
                }
            } else {
                $uploadStatus = "Terjadi kesalahan saat mengupload file.";
            }
        } else {
            $uploadStatus = "Ada error saat mengupload file.";
        }
    }
}

// Handle delete file
if (isset($_GET['delete'])) {
    $fileId = $_GET['delete'];

    // Fetch file details from database
    $sql = "SELECT file_name FROM files WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $fileId);
    $stmt->execute();
    $result = $stmt->get_result();
    $file = $result->fetch_assoc();

    if ($file) {
        // Delete file from the server
        $filePath = 'uploads/' . $file['file_name'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Delete file record from the database
        $sql = "DELETE FROM files WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $fileId);
        $stmt->execute();

        $uploadStatus = "File berhasil dihapus.";
    }
}

// Handle rename file
if (isset($_POST['rename'])) {
    $fileId = $_POST['file_id'];
    $newFileName = $_POST['new_file_name'];

    // Fetch current file details
    $sql = "SELECT file_name FROM files WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $fileId);
    $stmt->execute();
    $result = $stmt->get_result();
    $file = $result->fetch_assoc();

    if ($file) {
        // Old and new file paths
        $oldFilePath = 'uploads/' . $file['file_name'];
        $newFilePath = 'uploads/' . $newFileName;

        // Debugging: Check if the old file exists
        if (file_exists($oldFilePath)) {
            // Rename file on the server
            if (rename($oldFilePath, $newFilePath)) {
                // Update file name in the database
                $sql = "UPDATE files SET file_name = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $newFileName, $fileId);
                $stmt->execute();

                $uploadStatus = "File berhasil diubah namanya.";
            } else {
                $uploadStatus = "Terjadi kesalahan saat merubah nama file.";
            }
        } else {
            $uploadStatus = "File yang akan diubah namanya tidak ditemukan.";
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
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>clouDrive - File Uploader</h1>
    <h2>Simpan file mu disini!</h2>
    <div class="container">
        <h2>Upload File</h2>
        <form action="" method="POST" enctype="multipart/form-data">
            <input type="file" name="file" required>
            <button type="submit" name="submit">Upload</button>
        </form>
    </div>

    <h2>Daftar File</h2>
    <div>
        <label for="sort">Sort By:</label>
        <select id="sort" onchange="sortFiles()">
            <option value="upload_time" <?= $sortBy == 'upload_time' ? 'selected' : '' ?>>Upload Time</option>
            <option value="file_name" <?= $sortBy == 'file_name' ? 'selected' : '' ?>>File Name</option>
            <option value="file_size" <?= $sortBy == 'file_size' ? 'selected' : '' ?>>File Size</option>
        </select>

        <label for="order">Order:</label>
        <select id="order" onchange="sortFiles()">
            <option value="asc" <?= $order == 'asc' ? 'selected' : '' ?>>Ascending</option>
            <option value="desc" <?= $order == 'desc' ? 'selected' : '' ?>>Descending</option>
        </select>
    </div>

    <table border="1">
        <thead>
            <tr>
                <th>File Name</th>
                <th>File Size (KB)</th>
                <th>Upload Time</th>
                <th>Actions</th>
                <th>View/Download</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Adjust the query to sort based on user selection
            $sql = "SELECT * FROM files ORDER BY $sortBy $order";
            $result = $conn->query($sql);

            while($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['file_name'] . "</td>";
                echo "<td>" . round($row['file_size'] / 1024, 2) . "</td>"; // Convert bytes to KB
                echo "<td>" . $row['upload_time'] . "</td>";
                echo "<td>";
                // Delete button
                echo "<a href='?delete=" . $row['id'] . "' onclick='return confirm(\"Are you sure you want to delete this file?\")'>Delete</a> | ";
                // Rename button
                echo "<a href='#' onclick='showRenameForm(" . $row['id'] . ", \"" . htmlspecialchars($row['file_name']) . "\")'>Rename</a>";
                echo "</td>";
                echo "<td>";

                // Display View/Download based on file type
                $filePath = 'uploads/' . $row['file_name'];
                $fileExtension = strtolower(pathinfo($row['file_name'], PATHINFO_EXTENSION));

                if (in_array($fileExtension, ['jpg', 'jpeg', 'png'])) {
                    // Display image for image files
                    echo "<img src='$filePath' alt='Image' style='width:100px;height:auto;'>";
                } elseif ($fileExtension === 'pdf') {
                    // Provide a link to view PDF files
                    echo "<a href='$filePath' target='_blank'>View PDF</a>";
                } elseif ($fileExtension === 'docx') {
                    // Provide a link to download DOCX files
                    echo "<a href='$filePath' download>Download DOCX</a>";
                } else {
                    echo "<a href='$filePath' download>Download File</a>";
                }

                echo "</td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>

    <!-- Modal for Rename (only one instance) -->
    <div id="renameModal" style="display:none;">
        <form action="" method="POST">
            <input type="hidden" id="fileId" name="file_id">
            <label for="newFileName">New File Name:</label>
            <input type="text" id="newFileName" name="new_file_name" required>
            <button type="submit" name="rename">Rename File</button>
            <button type="button" onclick="closeRenameForm()">Cancel</button>
        </form>
    </div>

    <script>
        // Function to update the sorting URL
        function sortFiles() {
            var sortBy = document.getElementById('sort').value;
            var order = document.getElementById('order').value;
            window.location.href = "?sort_by=" + sortBy + "&order=" + order;
        }

        // Show rename form
        function showRenameForm(fileId, fileName) {
            document.getElementById('fileId').value = fileId;
            document.getElementById('newFileName').value = fileName;
            document.getElementById('renameModal').style.display = "block";
        }

        // Close rename form
        function closeRenameForm() {
            document.getElementById('renameModal').style.display = "none";
        }
    </script>
</body>
</html>
