<?php
include 'db_connection.php';

$plantId = isset($_GET['id']) ? intval($_GET['id']) : 0;
?>

<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Инвертори</title>
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

        .inverter-link {
            color: #1abc9c;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>
<body>

<header>
    <h1>Инвертори на соларна централа</h1>
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
        <li><a href="client_details.php?id=<?php echo $plantId; ?>">Назад</a></li>
    </ul>
</nav>

<div class="container">
    <div class="content">
        <h2>Инвертори:</h2>

        <table>
            <tr>
                <th>Име на инвертор</th>
                <th>Тип</th>
                <th>Текуща мощност</th>
                <th>Детайли</th>
            </tr>
            <?php
            try {
                // Подготвена заявка за извличане на инверторите за съответната соларна инсталация
                $stmt = $conn->prepare("SELECT * FROM Inverters WHERE PVPlants_PlantID = :plantId");
                $stmt->execute(['plantId' => $plantId]);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    // Извличане на текущата мощност за инвертора
                    $stmtPower = $conn->prepare("SELECT TotalPower FROM InverterData WHERE Inverters_InverterNr = :inverterNr ORDER BY Time DESC LIMIT 1");
                    $stmtPower->execute(['inverterNr' => $row['InverterNr']]);
                    $powerData = $stmtPower->fetch(PDO::FETCH_ASSOC);

                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($row['InverterName']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['InverterTypes_InverterType']) . '</td>';
                    echo '<td>' . ($powerData ? htmlspecialchars($powerData['TotalPower']) . ' kW' : 'Няма данни') . '</td>';
                    echo '<td><a class="inverter-link" href="inverter_details.php?id=' . htmlspecialchars($row['InverterNr']) . '">Виж детайли</a></td>';
                    echo '</tr>';
                }
            } catch (PDOException $e) {
                echo '<tr><td colspan="4">Грешка при извличане на данни: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
            }
            ?>
        </table>
    </div>
</div>

</body>
</html>
