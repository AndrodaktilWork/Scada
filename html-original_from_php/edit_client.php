<?php
include 'db_connection.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Проверка дали е зададено ID на централата
if (isset($_GET['plant_id'])) {
    $plantID = intval($_GET['plant_id']);
} elseif (isset($_GET['id'])) {
    // Опит да извлечем plant_id на базата на клиент ID
    $customerID = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT PlantID FROM PVPlants WHERE Customers_CustomerID = :customer_id LIMIT 1");
    $stmt->execute(['customer_id' => $customerID]);
    $plant = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($plant) {
        $plantID = $plant['PlantID'];
    } else {
        echo "Не е зададено ID на централата.";
        exit;
    }
} else {
    echo "Не е зададено ID на централата.";
    exit;
}

// Извличане на данните за централата
try {
    $stmt = $conn->prepare("SELECT * FROM PVPlants WHERE PlantID = :plant_id");
    $stmt->execute(['plant_id' => $plantID]);
    $plant = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$plant) {
        echo "Централата не е намерена!";
        exit;
    }

    // Извличане на свързаните инвертори
    $stmt2 = $conn->prepare("SELECT * FROM Inverters WHERE PVPlants_PlantID = :plant_id");
    $stmt2->execute(['plant_id' => $plantID]);
    $inverters = $stmt2->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Грешка при извличане на данните: " . htmlspecialchars($e->getMessage());
    exit;
}

// Обработка на формуляра за редакция на централата
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_plant'])) {
    $plantName = $_POST['plant_name'];
    $address = $_POST['address'];
    $country = $_POST['country'];
    $region = $_POST['region'];

    try {
        // Актуализиране на данните в базата
        $stmt = $conn->prepare("UPDATE PVPlants SET PlantName = :plant_name, Address = :address, Country = :country, Region = :region WHERE PlantID = :plant_id");
        $stmt->execute([
            'plant_name' => $plantName,
            'address' => $address,
            'country' => $country,
            'region' => $region,
            'plant_id' => $plantID
        ]);

        echo "Централата беше успешно актуализирана!";
    } catch (PDOException $e) {
        echo "Грешка при актуализирането на данните: " . htmlspecialchars($e->getMessage());
    }
}

// Обработка на формуляра за добавяне на нов инвертор
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_inverter'])) {
    $inverterName = $_POST['inverter_name'];
    $inverterType = $_POST['inverter_type'];

    try {
        $stmt = $conn->prepare("INSERT INTO Inverters (InverterName, InverterTypes_InverterType, PVPlants_PlantID) VALUES (:inverter_name, :inverter_type, :plant_id)");
        $stmt->execute([
            'inverter_name' => $inverterName,
            'inverter_type' => $inverterType,
            'plant_id' => $plantID
        ]);

        echo "Инверторът беше успешно добавен!";
    } catch (PDOException $e) {
        echo "Грешка при добавянето на инвертора: " . htmlspecialchars($e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редакция на централата</title>
    <style>
        body {
            font-family: 'Verdana', sans-serif;
            background-color: #f4f4f9;
            color: #333;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
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
            height: 100%;
            box-sizing: border-box;
            position: fixed;
            top: 0;
            left: 0;
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

        .form-container {
            display: flex;
            flex-direction: column;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            font-weight: bold;
        }

        .form-group input[type="text"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        input[type="submit"] {
            background-color: #1abc9c;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        input[type="submit"]:hover {
            background-color: #16a085;
        }

        .back-link {
            color: #1abc9c;
            text-decoration: none;
            font-weight: bold;
        }

        .form-box {
            margin-top: 30px;
            padding: 20px;
            border-radius: 8px;
            background-color: #f5f5f5;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>

<header>
    <h1>Редакция на централата</h1>
</header>

<nav>
    <ul>
        <li><a href="index.php">Начало</a></li>
    </ul>
</nav>

<div class="container">
    <div class="content">
        <h2>Информация за централата</h2>
        <form action="edit_client.php?plant_id=<?php echo htmlspecialchars($plantID); ?>" method="post" class="form-container">
            <div class="form-group">
                <label for="plant_name">Име на централата</label>
                <input type="text" id="plant_name" name="plant_name" value="<?php echo htmlspecialchars($plant['PlantName']); ?>" required>
            </div>
            <div class="form-group">
                <label for="address">Адрес</label>
                <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($plant['Address']); ?>" required>
            </div>
            <div class="form-group">
                <label for="country">Държава</label>
                <input type="text" id="country" name="country" value="<?php echo htmlspecialchars($plant['Country']); ?>" required>
            </div>
            <div class="form-group">
                <label for="region">Регион</label>
                <input type="text" id="region" name="region" value="<?php echo htmlspecialchars($plant['Region']); ?>" required>
            </div>
            <input type="submit" name="edit_plant" value="Запази промените">
        </form>
    </div>

    <div class="form-box">
        <h2>Инвертори</h2>
        <table>
            <tr>
                <th>Име на инвертор</th>
                <th>Тип</th>
                <th>Действия</th>
            </tr>
            <?php if (!empty($inverters)) : ?>
                <?php foreach ($inverters as $inverter) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($inverter['InverterName']); ?></td>
                        <td><?php echo htmlspecialchars($inverter['InverterTypes_InverterType']); ?></td>
                        <td>
                            <a href="edit_inverter.php?inverter_id=<?php echo $inverter['InverterNr']; ?>" class="btn-edit">Редактирай</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="3">Няма добавени инвертори.</td>
                </tr>
            <?php endif; ?>
        </table>
    </div>

    <!-- Формуляр за добавяне на нов инвертор -->
    <div class="form-box">
        <h2>Добавяне на нов инвертор</h2>
        <form action="edit_client.php?plant_id=<?php echo htmlspecialchars($plantID); ?>" method="post" class="form-container">
            <div class="form-group">
                <label for="inverter_name">Име на инвертора</label>
                <input type="text" id="inverter_name" name="inverter_name" placeholder="Име на инвертора" required>
            </div>
            <div class="form-group">
                <label for="inverter_type">Тип на инвертора</label>
                <input type="text" id="inverter_type" name="inverter_type" placeholder="Тип на инвертора" required>
            </div>
            <input type="submit" name="add_inverter" value="Добави инвертор">
        </form>
    </div>

    <a href="client_details.php?id=<?php echo htmlspecialchars($plant['Customers_CustomerID']); ?>" class="back-link">Назад към клиента</a>
</div>

</body>
</html>
