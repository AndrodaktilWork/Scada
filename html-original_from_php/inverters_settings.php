<?php
include 'db_connection.php';

// Fetch data if id is provided for editing
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM Inverters WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Insert or update logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Insert or update logic here (depends on whether $id is set)
    if (isset($id)) {
        // Update logic
        $stmt = $conn->prepare("UPDATE Inverters SET InverterNr = :InverterNr, Model = :Model, Manufacturer = :Manufacturer, Capacity = :Capacity WHERE id = :id");
        $stmt->execute([
            'InverterNr' => $_POST['InverterNr'],
            'Model' => $_POST['Model'],
            'Manufacturer' => $_POST['Manufacturer'],
            'Capacity' => $_POST['Capacity'],
            'id' => $id
        ]);
        echo "Инверторът беше успешно актуализиран!";
    } else {
        // Insert logic
        $stmt = $conn->prepare("INSERT INTO Inverters (InverterNr, Model, Manufacturer, Capacity) VALUES (:InverterNr, :Model, :Manufacturer, :Capacity)");
        $stmt->execute([
            'InverterNr' => $_POST['InverterNr'],
            'Model' => $_POST['Model'],
            'Manufacturer' => $_POST['Manufacturer'],
            'Capacity' => $_POST['Capacity']
        ]);
        echo "Инверторът беше успешно създаден!";
    }
}

// Delete logic
if (isset($_POST['delete'])) {
    $stmt = $conn->prepare("DELETE FROM Inverters WHERE id = :id");
    $stmt->execute(['id' => $id]);
    echo "Инверторът беше успешно изтрит!";
    exit;
}
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inverters Settings</title>
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
        <h1>Настройки на инвертори</h1>
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
            <h2>Настройки за инвертори</h2>

            <form method="post">
                <label for="InverterNr">Номер на инвертор</label>
                <input type="text" name="InverterNr" value="<?php echo $data['InverterNr'] ?? ''; ?>" required>

                <label for="Model">Модел</label>
                <input type="text" name="Model" value="<?php echo $data['Model'] ?? ''; ?>" required>

                <label for="Manufacturer">Производител</label>
                <input type="text" name="Manufacturer" value="<?php echo $data['Manufacturer'] ?? ''; ?>" required>

                <label for="Capacity">Капацитет</label>
                <input type="text" name="Capacity" value="<?php echo $data['Capacity'] ?? ''; ?>" required>

                <input type="submit" name="save" value="Запази">
                <?php if (isset($id)): ?>
                <input type="submit" name="delete" value="Изтрий">
                <?php endif; ?>
            </form>
        </div>
    </div>
</body>
</html>
