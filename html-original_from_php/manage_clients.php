<?php
include 'db_connection.php';

// Показване на грешки за отстраняване на проблеми
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Изтриване на клиент и свързаните данни
if (isset($_GET['delete'])) {
    $customerId = intval($_GET['delete']);
    try {
        $conn->beginTransaction();

        // Изтриване на свързаните записи в таблицата Users
        $stmt1 = $conn->prepare("DELETE FROM Users WHERE Customers_CustomerID = :id");
        $stmt1->execute(['id' => $customerId]);

        // Изтриване на свързаните записи в зависимите таблици
        $stmt2 = $conn->prepare("DELETE FROM InverterData WHERE Inverters_InverterNr IN (SELECT InverterNr FROM Inverters WHERE PVPlants_PlantID IN (SELECT PlantID FROM PVPlants WHERE Customers_CustomerID = :id))");
        $stmt2->execute(['id' => $customerId]);

        $stmt3 = $conn->prepare("DELETE FROM Inverters WHERE PVPlants_PlantID IN (SELECT PlantID FROM PVPlants WHERE Customers_CustomerID = :id)");
        $stmt3->execute(['id' => $customerId]);

        $stmt4 = $conn->prepare("DELETE FROM PVPlants WHERE Customers_CustomerID = :id");
        $stmt4->execute(['id' => $customerId]);

        $stmt5 = $conn->prepare("DELETE FROM Customers WHERE CustomerID = :id");
        $stmt5->execute(['id' => $customerId]);

        $conn->commit();

        header("Location: manage_clients.php");
        exit;
    } catch (PDOException $e) {
        $conn->rollBack();
        echo "Грешка при изтриване на клиента: " . htmlspecialchars($e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Клиенти</title>
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

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 10px;
            border: 1px solid #ddd;
        }

        th {
            background-color: #f5f5f5;
        }

        a.client-link {
            color: #1abc9c;
            text-decoration: none;
        }

        a.client-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<header>
    <h1>Клиенти</h1>
</header>

<nav>
    <ul>
        <li><a href="index.php">Начало</a></li>
        <li><a href="manage_clients.php">Настройки</a></li>
    </ul>
</nav>

<div class="container">
    <div class="content">
        <h2>Съществуващи клиенти</h2>

        <table>
            <tr>
                <th>ID</th>
                <th>Име на клиента</th>
                <th>Представител</th>
                <th>Основен контакт</th>
                <th>Действия</th>
            </tr>
            <?php
            try {
                // Подготвена заявка за извличане на клиенти
                $stmt = $conn->prepare("SELECT * FROM Customers");
                $stmt->execute();
                
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($row['CustomerID']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['CustomerName']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['Representative']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['PrimaryContact']) . '</td>';
                    echo '<td>';
                    // Линк за изтриване
                    echo '<a class="client-link" href="manage_clients.php?delete=' . htmlspecialchars($row['CustomerID']) . '">Изтрий</a>';
                    // Линк за редактиране
                    echo '<a class="client-link" href="edit_client.php?id=' . htmlspecialchars($row['CustomerID']) . '">Редактирай</a>';
                    echo '</td>';
                    echo '</tr>';
                }
            } catch (PDOException $e) {
                echo '<tr><td colspan="5">Грешка при извличане на данни: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
            }
            ?>
        </table>
    </div>
</div>

</body>
</html>
