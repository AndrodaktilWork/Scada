<?php
include 'db_connection.php';

$inverterId = isset($_GET['id']) ? intval($_GET['id']) : 0;
?>

<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Данни за инверторите</title>
    <style>
        body {
            font-family: 'Verdana', sans-serif;
            background-color: #f4f4f9;
            color: #333;
            margin: 0;
            padding: 0;
            display: flex;
            min-height: 100vh;
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
    </style>
</head>
<body>

<header>
    <h1>СИСТЕМА ЗА МОНИТОРИНГ И КОНТРОЛ НА ФЕЦ | TECHNOSUN.BG</h1>
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
        <li><a href="plant_details.php?id=<?php echo $inverterId; ?>">Назад</a></li>
    </ul>
</nav>

<div class="container">
    <div class="content">
        <h2>Данни на инверторите:</h2>

        <?php
        try {
            // Подготвена заявка за извличане на данни за инвертора
            $stmt = $conn->prepare("SELECT * FROM InverterData WHERE Inverters_InverterNr = :inverterId ORDER BY Time DESC LIMIT 1");
            $stmt->execute(['inverterId' => $inverterId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                echo '<table>';
                echo '<tr><th>Параметър</th><th>Стойност</th></tr>';
                echo '<tr><td>AC Напрежение (L1)</td><td>' . htmlspecialchars($row['ACVoltageL1']) . ' V</td></tr>';
                echo '<tr><td>AC Ток (L1)</td><td>' . htmlspecialchars($row['ACCurrentL1']) . ' A</td></tr>';
                echo '<tr><td>DC Напрежение</td><td>' . htmlspecialchars($row['DCVoltage']) . ' V</td></tr>';
                echo '<tr><td>DC Ток</td><td>' . htmlspecialchars($row['DCCurrent']) . ' A</td></tr>';
                echo '<tr><td>Температура на инвертора</td><td>' . htmlspecialchars($row['InverterTemp']) . ' °C</td></tr>';
                echo '<tr><td>Обща мощност</td><td>' . htmlspecialchars($row['TotalPower']) . ' kW</td></tr>';
                echo '<tr><td>Дата и час</td><td>' . htmlspecialchars($row['Time']) . '</td></tr>';
                echo '</table>';
            } else {
                echo '<p>Няма налични данни за този инвертор.</p>';
            }
        } catch (PDOException $e) {
            echo "Грешка при извличане на данни: " . htmlspecialchars($e->getMessage());
        }
        ?>
    </div>
</div>

</body>
</html>
