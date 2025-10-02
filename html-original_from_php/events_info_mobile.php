<?php
// Включване на връзката към базата данни
include 'db_connection.php';

// Извличане на всички клиенти от базата данни
$stmt = $conn->prepare("SELECT * FROM Customers");
$stmt->execute();
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Извличане на всички техници от базата данни
$techStmt = $conn->prepare("SELECT * FROM technical_support");
$techStmt->execute();
$technicians = $techStmt->fetchAll(PDO::FETCH_ASSOC);

// Създаване на асоциативен масив за техниците
$techArray = [];
foreach ($technicians as $tech) {
    $techArray[$tech['TechID']] = $tech['TechName'];
}

// Обработка на формата за назначаване на отговорник и статус
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $eventId = $_POST['event_id'];
    $assignedTo = $_POST['assigned_to'];
    $status = $_POST['status'];

    $updateStmt = $conn->prepare("UPDATE Events SET AssignedTo = ?, Status = ? WHERE EventID = ?");
    $updateStmt->execute([$assignedTo, $status, $eventId]);

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>СКАДА СИСТЕМА</title>
    <meta http-equiv="refresh" content="10"> <!-- Автоматично обновяване на страницата на всеки 10 секунди -->
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

        /* Централен контейнер, който изолира съдържанието */
        .container {
            width: 100%;
            max-width: 1200px;
            padding: 20px;
            box-sizing: border-box;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            margin: 20px auto;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        h1 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #34495e;
            text-align: center;
        }

        /* Контейнер за менюто */
        .menu-container {
            width: 100%;
            background-color: #2c3e50;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            box-sizing: border-box;
        }

        .menu-container a {
            color: white;
            text-decoration: none;
            font-weight: bold;
            padding: 10px;
            background-color: #34495e;
            border-radius: 5px;
            text-align: center;
            transition: background-color 0.3s;
            font-size: 14px;
        }

        .menu-container a:hover {
            background-color: #1abc9c;
        }

        .content {
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .title-box {
            background-color: #2c7c31;
            color: white;
            padding: 15px;
            text-align: center;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            font-size: 20px;
        }

        .client-box {
            margin-bottom: 40px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #ffecd2;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .client-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.2);
        }

        .field-box {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #e0f7fa;
            transition: background-color 0.3s;
        }

        .field-box:hover {
            background-color: #b2ebf2;
        }

        .inverter-box {
            margin-top: 15px;
            padding: 10px;
            border: 1px solid #bbb;
            border-radius: 5px;
            background-color: #f0f0f0;
            transition: background-color 0.3s, box-shadow 0.3s;
        }

        .inverter-box:hover {
            background-color: #e0e0e0;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        footer {
            text-align: center;
            padding: 10px;
            font-size: 12px;
            color: #777;
            margin-top: auto;
        }

        .form-container {
            margin-top: 20px;
        }

        .form-container label {
            display: block;
            margin-bottom: 5px;
        }

        .form-container select, .form-container input[type="submit"] {
            margin-bottom: 10px;
            font-family: 'Verdana', sans-serif;
            font-size: 14px;
        }

        .severity-high {
            color: red;
            font-weight: bold;
        }

        .severity-medium {
            color: orange;
            font-weight: bold;
        }

        .severity-low {
            color: green;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>СКАДА СИСТЕМА</h1>

        <!-- Контейнер за менюто -->
        <div class="menu-container">
            <a href="index.php">Дашборд</a>
            <a href="customers_info.php">Клиенти</a>
            <a href="events_info.php">Събития</a>
            <a href="inverters_info.php">Инвертори</a>
            <a href="users_info.php">Потребители</a>
            <a href="settings.php">Настройки</a>
        </div>

        <div class="content">
            <div class="title-box">
                Информация за събитията
            </div>

            <?php
            foreach ($customers as $customer) {
                echo '<div class="client-box">';
                echo '<h3>Клиент: ' . htmlspecialchars($customer['CustomerName']) . '</h3>';

                $fieldsStmt = $conn->prepare("SELECT * FROM PVPlants WHERE CustomerID = ?");
                $fieldsStmt->execute([$customer['CustomerID']]);
                $fields = $fieldsStmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($fields as $field) {
                    echo '<div class="field-box">';
                    echo '<h4>Централа: ' . htmlspecialchars($field['PlantName']) . '</h4>';

                    $invertersStmt = $conn->prepare("SELECT * FROM Inverters WHERE PlantID = ? AND CustomerID = ?");
                    $invertersStmt->execute([$field['PlantID'], $customer['CustomerID']]);
                    $inverters = $invertersStmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($inverters as $inverter) {
                        echo '<div class="inverter-box">';
                        echo '<h5>Инвертор: ' . htmlspecialchars($inverter['InverterName']) . '</h5>';

                        if (!empty($inverter['InvAlarms'])) {
                            echo '<p><strong>Аларми: </strong>' . htmlspecialchars($inverter['InvAlarms']) . '</p>';
                        }

                        $alarmsStmt = $conn->prepare("SELECT * FROM Events WHERE PlantID = ? AND InverterID = ? AND CustomerID = ?");
                        $alarmsStmt->execute([$field['PlantID'], $inverter['InverterID'], $customer['CustomerID']]);
                        $alarms = $alarmsStmt->fetchAll(PDO::FETCH_ASSOC);

                        if (count($alarms) > 0) {
                            echo '<table>';
                            echo '<thead>';
                            echo '<tr>';
                            echo '<th>Време</th>';
                            echo '<th>Съобщение</th>';
                            echo '<th>Сериозност</th>';
                            echo '<th>Назначено на</th>';
                            echo '<th>Статус</th>';
                            echo '<th>Действие</th>';
                            echo '</tr>';
                            echo '</thead>';
                            echo '<tbody>';

                            foreach ($alarms as $alarm) {
                                $severityClass = '';
                                if ($alarm['Severity'] == 'Висока') {
                                    $severityClass = 'severity-high';
                                } elseif ($alarm['Severity'] == 'Средна') {
                                    $severityClass = 'severity-medium';
                                } elseif ($alarm['Severity'] == 'Ниска') {
                                    $severityClass = 'severity-low';
                                }

                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($alarm['Time']) . '</td>';
                                echo '<td>' . htmlspecialchars($alarm['Message']) . '</td>';
                                echo '<td class="' . $severityClass . '">' . htmlspecialchars($alarm['Severity']) . '</td>';
                                echo '<td>' . htmlspecialchars($techArray[$alarm['AssignedTo']] ?? 'Не определен') . '</td>';
                                echo '<td>' . htmlspecialchars($alarm['Status']) . '</td>';
                                echo '<td>';
                                echo '<form method="post" action="">';
                                echo '<input type="hidden" name="event_id" value="' . htmlspecialchars($alarm['EventID']) . '">';
                                echo '<label for="assigned_to">Назначи на:</label>';
                                echo '<select name="assigned_to" id="assigned_to" style="font-family: \'Verdana\', sans-serif; font-size: 16px;">';
                                foreach ($techArray as $techID => $techName) {
                                    $selected = ($techID == $alarm['AssignedTo']) ? 'selected' : '';
                                    echo '<option value="' . htmlspecialchars($techID) . '" ' . $selected . '>' . htmlspecialchars($techName) . '</option>';
                                }
                                echo '</select>';
                                echo '<label for="status">Статус:</label>';
                                echo '<select name="status" id="status" style="font-family: \'Verdana\', sans-serif; font-size: 16px;">';
                                $statuses = ['Отворено', 'В процес', 'Затворено'];
                                foreach ($statuses as $status) {
                                    $selectedStatus = ($status == $alarm['Status']) ? 'selected' : '';
                                    echo '<option value="' . htmlspecialchars($status) . '" ' . $selectedStatus . '>' . htmlspecialchars($status) . '</option>';
                                }
                                echo '</select>';
                                echo '<input type="submit" value="Актуализирай">';
                                echo '</form>';
                                echo '</td>';
                                echo '</tr>';
                            }

                            echo '</tbody>';
                            echo '</table>';
                        } else {
                            echo '<p>Няма аларми за този инвертор.</p>';
                        }

                        echo '</div>';
                    }

                    echo '</div>';
                }

                echo '</div>';
            }
            ?>
        </div>
    </div>

    <footer>
        TECHNOSUN.BG | КОНТРОЛ НА СОЛАРНИ ПАРКОВЕ
    </footer>
</body>
</html>
