<?php
include 'db_connection.php';

$customerId = isset($_GET['id']) ? intval($_GET['id']) : 0;

$stmt = $conn->prepare("SELECT * FROM Customers WHERE CustomerID = :id");
$stmt->execute(['id' => $customerId]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    echo "Клиентът не е намерен!";
    exit;
}

$stmtPlants = $conn->prepare("SELECT * FROM PVPlants WHERE Customers_CustomerID = :id");
$stmtPlants->execute(['id' => $customerId]);
$plants = $stmtPlants->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Детайли на клиента</title>
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
            min-height: 100vh;
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
        }

        .content {
            padding: 20px;
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

        .client-link {
            color: #1abc9c;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>
<body>

<header>
    <h1>Детайли на клиента: <?php echo htmlspecialchars($customer['CustomerName']); ?></h1>
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
        <h2>Информация за клиента</h2>
        <table>
            <tr>
                <th>Име на клиент</th>
                <td><?php echo htmlspecialchars($customer['CustomerName']); ?></td>
            </tr>
            <tr>
                <th>Представител</th>
                <td><?php echo htmlspecialchars($customer['Representative']); ?></td>
            </tr>
            <tr>
                <th class="table-small-column">Основен контакт</th>
                <td><?php echo htmlspecialchars($customer['PrimaryContact']); ?></td>
            </tr>
        </table>

        <h2>Централи на клиента</h2>
        <table>
            <tr>
                <th>Име на централа</th>
                <th>Адрес</th>
                <th>Регион</th>
                <th>Държава</th>
                <th>Детайли</th>
            </tr>
            <?php foreach ($plants as $plant) : ?>
                <tr>
                    <td><?php echo htmlspecialchars($plant['PlantName']); ?></td>
                    <td><?php echo htmlspecialchars($plant['Address']); ?></td>
                    <td><?php echo htmlspecialchars($plant['Region']); ?></td>
                    <td><?php echo htmlspecialchars($plant['Country']); ?></td>
                    <td><a class="client-link" href="plant_details.php?id=<?php echo $plant['PlantID']; ?>">Виж детайли</a></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

</body>
</html>
