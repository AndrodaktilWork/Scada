<?php
include 'db_connection.php';

// Fetch data if id is provided for editing
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM Events WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Insert or update logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Insert or update logic here (depends on whether $id is set)
    if (isset($id)) {
        // Update logic
        $stmt = $conn->prepare("UPDATE Events SET Time = :Time, Message = :Message, Severity = :Severity, AssignedTo = :AssignedTo, Status = :Status WHERE id = :id");
        $stmt->execute([
            'Time' => $_POST['Time'],
            'Message' => $_POST['Message'],
            'Severity' => $_POST['Severity'],
            'AssignedTo' => $_POST['AssignedTo'],
            'Status' => $_POST['Status'],
            'id' => $id
        ]);
        echo "Събитието беше успешно актуализирано!";
    } else {
        // Insert logic
        $stmt = $conn->prepare("INSERT INTO Events (Time, Message, Severity, AssignedTo, Status) VALUES (:Time, :Message, :Severity, :AssignedTo, :Status)");
        $stmt->execute([
            'Time' => $_POST['Time'],
            'Message' => $_POST['Message'],
            'Severity' => $_POST['Severity'],
            'AssignedTo' => $_POST['AssignedTo'],
            'Status' => $_POST['Status']
        ]);
        echo "Събитието беше успешно създадено!";
    }
}

// Delete logic
if (isset($_POST['delete'])) {
    $stmt = $conn->prepare("DELETE FROM Events WHERE id = :id");
    $stmt->execute(['id' => $id]);
    echo "Събитието беше успешно изтрито!";
    exit;
}
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events Settings</title>
    <style>
        body {
            font-family: 'Verdana', sans-serif;
            background-color: #f4f4f9;
            color: #333;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
        }

        header {
            background-color: #34495e;
            color: white;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        nav {
            background-color: #2c3e50;
            padding: 15px;
            width: 250px;
            box-sizing: border-box;
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            z-index: 1000;
        }

        nav ul {
            list-style-type: none;
            padding: 0;
        }

        nav ul li {
            margin-bottom: 15px;
        }

        nav ul li a {
            color: white;
            text-decoration: none;
            font-weight: bold;
            display: block;
            padding: 10px 15px;
            border-radius: 5px;
            background-color: #34495e;
            transition: background-color 0.3s;
        }

        nav ul li a:hover {
            background-color: #1abc9c;
        }

        .container {
            margin-left: 270px;
            padding: 20px;
            flex: 1;
            max-width: calc(100% - 270px);
            box-sizing: border-box;
        }

        .content {
            padding: 20px;
            margin: 0 auto;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #34495e;
            text-align: center;
        }

        form {
            margin-top: 20px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        label {
            font-weight: bold;
        }

        input[type="text"] {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            width: 100%;
        }

        input[type="submit"] {
            padding: 10px;
            background-color: #1abc9c;
            border: none;
            border-radius: 5px;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        input[type="submit"]:hover {
            background-color: #16a085;
        }
    </style>
</head>
<body>
    <header>
        <h1>Настройки на събития</h1>
    </header>
    
    <nav>
        <ul>
            <li><a href="index.php">Начало</a></li>
            <li><a href="customers_info.php">Клиенти</a></li>
            <li><a href="events_info.php">Събития</a></li>
            <li><a href="inverters_info.php">Инвертори</a></li>
            <li><a href="users_info.php">Потребители</a></li>
            <li><a href="customers_settings.php">Настройки на клиенти</a></li>
            <li><a href="events_settings.php">Настройки на събития</a></li>
            <li><a href="inverters_settings.php">Настройки на инвертори</a></li>
            <li><a href="users_settings.php">Настройки на потребители</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="content">
            <h2>Настройки за събитията</h2>

            <form method="post">
                <label for="Time">Време</label>
                <input type="text" name="Time" value="<?php echo $data['Time'] ?? ''; ?>" required>

                <label for="Message">Съобщение</label>
                <input type="text" name="Message" value="<?php echo $data['Message'] ?? ''; ?>" required>

                <label for="Severity">Сериозност</label>
                <input type="text" name="Severity" value="<?php echo $data['Severity'] ?? ''; ?>" required>

                <label for="AssignedTo">Назначено на</label>
                <input type="text" name="AssignedTo" value="<?php echo $data['AssignedTo'] ?? ''; ?>" required>

                <label for="Status">Статус</label>
                <input type="text" name="Status" value="<?php echo $data['Status'] ?? ''; ?>" required>

                <input type="submit" name="save" value="Запази">
                <?php if (isset($id)): ?>
                <input type="submit" name="delete" value="Изтрий">
                <?php endif; ?>
            </form>
        </div>
    </div>
</body>
</html>
