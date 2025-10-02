<?php
// Включване на връзката към базата данни
include 'db_connection.php';

// Извличане на всички клиенти от базата данни
$stmt = $conn->prepare("SELECT * FROM Customers"); // Подготвяме SQL заявка за извличане на всички клиенти
$stmt->execute(); // Изпълняваме заявката
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC); // Получаваме всички резултати като асоциативен масив

// Извличане на всички техници от базата данни
$techStmt = $conn->prepare("SELECT * FROM technical_support"); // Подготвяме SQL заявка за извличане на всички техници
$techStmt->execute(); // Изпълняваме заявката
$technicians = $techStmt->fetchAll(PDO::FETCH_ASSOC); // Получаваме всички резултати като асоциативен масив

// Създаване на асоциативен масив за техниците, където ключът е TechID, а стойността е TechName
$techArray = [];
foreach ($technicians as $tech) {
    $techArray[$tech['TechID']] = $tech['TechName']; // Добавяме техниците в масива с ключ TechID и стойност TechName
}

// Обработка на формата за назначаване на отговорник и статус
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $eventId = $_POST['event_id']; // Получаваме ID на събитието от POST заявката
    $assignedTo = $_POST['assigned_to']; // Получаваме ID на назначения техник от POST заявката
    $status = $_POST['status']; // Получаваме новия статус от POST заявката

    // Подготвяме SQL заявка за обновяване на записа в таблицата Events
    $updateStmt = $conn->prepare("UPDATE Events SET AssignedTo = ?, Status = ? WHERE EventID = ?");
    $updateStmt->execute([$assignedTo, $status, $eventId]); // Изпълняваме заявката с получените данни

    header("Location: " . $_SERVER['PHP_SELF']); // Презареждаме страницата след успешното обновление
    exit; // Прекратяваме изпълнението на скрипта, за да предотвратим повторно изпращане на данните
}
?>

<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events Information</title>
    <meta http-equiv="refresh" content="10"> <!-- Автоматично обновяване на страницата на всеки 10 секунди -->
    <style>
        body {
            font-family: 'Verdana', sans-serif; /* Основен шрифт за страницата */
            background-color: #f4f4f9; /* Цвят на фона на страницата */
            color: #333; /* Основен цвят на текста */
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
        }

        header {
            background-color: #34495e; /* Фон на заглавката */
            color: white; /* Цвят на текста в заглавката */
            padding: 1px; /* Вътрешно отстояние на заглавката */
            text-align: center; /* Центриране на текста в заглавката */
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); /* Леко сенчест ефект */
            font-size: 10px; /* Размер на шрифта в заглавката */
        }

        nav {
            background-color: #2c3e50; /* Фон на навигационната лента */
            padding: 15px;
            width: 250px;
            box-sizing: border-box; /* Включване на вътрешното отстояние в ширината */
            position: fixed; /* Фиксиране на навигацията отляво */
            top: 0;
            left: 0;
            height: 100%; /* Височина на навигационната лента */
            z-index: 1000; /* Позициониране над останалите елементи */
        }

        nav ul {
            list-style-type: none; /* Премахване на стандартните списъчни маркери */
            padding: 0; /* Премахване на вътрешното отстояние */
        }

        nav ul li {
            margin-bottom: 15px; /* Разстояние между елементите в навигацията */
        }

        nav ul li a {
            color: white; /* Цвят на текста на линковете */
            text-decoration: none; /* Премахване на подчертаването на линковете */
            font-weight: bold; /* Дебел шрифт за линковете */
            display: block; /* Линковете заемат цялото налично пространство */
            padding: 10px 15px; /* Вътрешно отстояние на линковете */
            border-radius: 5px; /* Заобляне на ъглите на линковете */
            background-color: #34495e; /* Фон на линковете */
            transition: background-color 0.3s; /* Плавен преход на фона при задържане на мишката */
        }

        nav ul li a:hover {
            background-color: #1abc9c; /* Промяна на фона на линка при задържане на мишката */
        }

        .container {
            margin-left: 270px; /* Отстояние отляво за навигационната лента */
            padding: 20px;
            flex: 1; /* Контейнерът заема цялото останало пространство */
            max-width: calc(100% - 270px); /* Максимална ширина на контейнера */
            box-sizing: border-box; /* Включване на вътрешното отстояние в ширината */
        }

        .content {
            padding: 20px;
            background-color: white; /* Фон на съдържанието */
            border-radius: 8px; /* Заобляне на ъглите на съдържанието */
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1); /* Леко сенчест ефект за съдържанието */
        }

        /* СТИЛ НА БОКСА ЗА ЗАГЛАВИЕТО */
        .title-box {
            background-color: #2c7c31; /* Тъмнозелен фон */
            color: white; /* Бял текст */
            padding: 15px; /* Вътрешно отстояние на заглавието */
            border-radius: 8px; /* Заобляне на ъглите на бокса */
            text-align: center; /* Центриране на текста */
            margin-bottom: 20px; /* Отстояние под бокса */
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); /* Леко сенчест ефект */
            max-width: 100%; /* Ширина на заглавния бокс */
            font-size: 20px; /* Размер на шрифта в заглавния бокс */
            font-weight: normal; /* Премахване на удебеляването на текста */
        }

        h2 {
            color: white; /* Бял цвят на заглавието */
            text-align: center; /* Центриране на заглавието */
            font-size: 20px; /* Размер на шрифта на заглавието */
            margin-bottom: 20px; /* Отстояние под заглавието */
        }

        .client-box {
            margin-bottom: 40px; /* Отстояние под всяка клиентска кутия */
            padding: 20px;
            border: 1px solid #ddd; /* Светлосива рамка около всяка клиентска кутия */
            border-radius: 8px; /* Заобляне на ъглите на кутията */
            background-color: #ffecd2; /* Светъл фон на кутията */
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); /* Леко сенчест ефект за кутията */
            transition: transform 0.3s, box-shadow 0.3s; /* Плавен преход при задържане на мишката */
        }

        .client-box:hover {
            transform: translateY(-5px); /* Леко повдигане на кутията при задържане на мишката */
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.2); /* Усилване на сенчестия ефект при задържане на мишката */
        }

        .field-box {
            margin-top: 20px; /* Отстояние над всяко поле */
            padding: 15px;
            border: 1px solid #ccc; /* Светлосива рамка около всяко поле */
            border-radius: 5px; /* Заобляне на ъглите на полето */
            background-color: #e0f7fa; /* Светъл фон на полето */
            transition: background-color 0.3s; /* Плавен преход на фона при задържане на мишката */
        }

        .field-box:hover {
            background-color: #b2ebf2; /* Промяна на фона на полето при задържане на мишката */
        }

        .inverter-box {
            margin-top: 15px; /* Отстояние над всяка инверторна кутия */
            padding: 10px;
            border: 1px solid #bbb; /* Светлосива рамка около инверторната кутия */
            border-radius: 5px; /* Заобляне на ъглите на кутията */
            background-color: #f0f0f0; /* Светъл фон на инверторната кутия */
            transition: background-color 0.3s, box-shadow 0.3s; /* Плавен преход на фона и сенките при задържане на мишката */
        }

        .inverter-box:hover {
            background-color: #e0e0e0; /* Промяна на фона на инверторната кутия при задържане на мишката */
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); /* Усилване на сенчестия ефект при задържане на мишката */
        }

        table {
            width: 100%; /* Ширината на таблицата да бъде 100% */
            border-collapse: collapse; /* Премахване на празното пространство между клетките на таблицата */
            margin-top: 15px; /* Отстояние над таблицата */
        }

        table, th, td {
            border: 1px solid #ddd; /* Граница около таблицата и клетките */
        }

        th, td {
            padding: 10px; /* Вътрешно отстояние на клетките */
            text-align: left; /* Подравняване на текста в клетките вляво */
        }

        th {
            background-color: #f2f2f2; /* Светъл фон на заглавните клетки */
        }

        .form-container {
            margin-top: 20px; /* Отстояние над формуляра */
        }

        .form-container label {
            display: block; /* Етикетите да заемат цялото налично пространство */
            margin-bottom: 5px; /* Отстояние под етикета */
        }

        .form-container select, .form-container input[type="submit"] {
            margin-bottom: 10px; /* Отстояние под избирачите и бутоните */
            font-family: 'Verdana', sans-serif; /* ШРИФТ НА ПАДАЩОТО МЕНЮ */
            font-size: 14px; /* РАЗМЕР НА ШРИФТА НА ПАДАЩОТО МЕНЮ */
        }

        /* Динамични цветове за алармите */
        .severity-high {
            color: red; /* Червен текст за аларми с висока сериозност */
            font-weight: bold; /* Удебелен текст за аларми с висока сериозност */
        }

        .severity-medium {
            color: orange; /* Оранжев текст за аларми със средна сериозност */
            font-weight: bold; /* Удебелен текст за аларми със средна сериозност */
        }

        .severity-low {
            color: green; /* Зелен текст за аларми с ниска сериозност */
            font-weight: bold; /* Удебелен текст за аларми с ниска сериозност */
        }
    </style>
</head>
<body>
    <header>
        <h1>ИНФОРМАЦИОНЕН ПАНЕЛ | СКАДА СИСТЕМА ЗА КОНТРОЛ НА СОЛАРНИ ПАРКОВЕ | ТЕХНОСЪН ООД</h1>
    </header>
    
    <nav>
        <ul>
            <li><a href="index.php">Дашборд</a></li>
            <li><a href="customers_info.php">Клиенти</a></li>
            <li><a href="events_info.php">Събития</a></li>
            <li><a href="inverters_info.php">Инвертори</a></li>
            <li><a href="users_info.php">Потребители</a></li>
            <li><a href="settings.php">Настройки</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="content">
            <!-- ЗАГЛАВКА ВЪВ ВИД НА БОКС -->
            <div class="title-box">
                Информация за събитията
            </div>

            <?php
            // Извеждаме информацията за всеки клиент
            foreach ($customers as $customer) {
                echo '<div class="client-box">';
                echo '<h3>Клиент: ' . htmlspecialchars($customer['CustomerName']) . '</h3>';

                // Извличане на полетата (соларни централи) за конкретния клиент
                $fieldsStmt = $conn->prepare("SELECT * FROM PVPlants WHERE CustomerID = ?");
                $fieldsStmt->execute([$customer['CustomerID']]);
                $fields = $fieldsStmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($fields as $field) {
                    echo '<div class="field-box">';
                    echo '<h4>Централа: ' . htmlspecialchars($field['PlantName']) . '</h4>';

                    // Извличане на инверторите за конкретното поле
                    $invertersStmt = $conn->prepare("SELECT * FROM Inverters WHERE PlantID = ? AND CustomerID = ?");
                    $invertersStmt->execute([$field['PlantID'], $customer['CustomerID']]);
                    $inverters = $invertersStmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($inverters as $inverter) {
                        echo '<div class="inverter-box">';
                        echo '<h5>Инвертор: ' . htmlspecialchars($inverter['InverterName']) . '</h5>';

                        // Показване на алармите от колоната InvAlarms
                        if (!empty($inverter['InvAlarms'])) {
                            echo '<p><strong>Аларми: </strong>' . htmlspecialchars($inverter['InvAlarms']) . '</p>';
                        }

                        // Извличане на алармите за конкретния инвертор от таблицата Events
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
                                // Определяне на класа за сериозността на алармата
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
                                echo '<select name="assigned_to" id="assigned_to" style="font-family: \'Verdana\', sans-serif; font-size: 16px;">'; // ШРИФТ И РАЗМЕР НА ПАДАЩОТО МЕНЮ
                                foreach ($techArray as $techID => $techName) {
                                    $selected = ($techID == $alarm['AssignedTo']) ? 'selected' : '';
                                    echo '<option value="' . htmlspecialchars($techID) . '" ' . $selected . '>' . htmlspecialchars($techName) . '</option>';
                                }
                                echo '</select>';
                                echo '<label for="status">Статус:</label>';
                                echo '<select name="status" id="status" style="font-family: \'Verdana\', sans-serif; font-size: 16px;">'; // ШРИФТ И РАЗМЕР НА ПАДАЩОТО МЕНЮ
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

                        echo '</div>'; // Край на инверторната кутия
                    }

                    echo '</div>'; // Край на полевата кутия
                }

                echo '</div>'; // Край на клиентската кутия
            }
            ?>
        </div>
    </div>
</body>
</html>
