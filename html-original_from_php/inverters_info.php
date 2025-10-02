<?php
// Включване на връзката към базата данни
include 'db_connection.php';

// Извличане на информация за инверторите, клиентите и полетата
$stmt = $conn->prepare("
    SELECT i.InverterName, i.SerialNumber, i.Capacity, i.PhaseType, i.Strings, i.PanelCount, i.UsageType, i.ZeroExportSetting, 
           i.InvAlarms, c.CustomerName, p.PlantName 
    FROM Inverters i
    JOIN Customers c ON i.CustomerID = c.CustomerID
    JOIN PVPlants p ON i.PlantID = p.PlantID
");
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inverters Information</title>
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

        /* Стил за клетките с аларми */
        .alarm-cell {
            color: red; /* Червен текст */
            font-weight: bold; /* Болднат текст */
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
                Информация за инверторите
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Инвертор</th>
                        <th>SN Номер</th>
                        <th>Клиент</th>
                        <th>Централа</th>
                        <th>Мощност</th>
                        <th>1P / 3P</th>
                        <th>Стрингове</th> <!-- Новата колона "Стрингове" -->
                        <th>Брой панели</th>
                        <th>Тип употреба</th>
                        <th>Power To Zero</th>
                        <th>Аларми</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['InverterName']); ?></td>
                        <td><?php echo htmlspecialchars($row['SerialNumber']); ?></td>
                        <td><?php echo htmlspecialchars($row['CustomerName']); ?></td>
                        <td><?php echo htmlspecialchars($row['PlantName']); ?></td>
                        <td><?php echo htmlspecialchars($row['Capacity']); ?></td>
                        <td><?php echo htmlspecialchars($row['PhaseType']); ?></td>
                        <td><?php echo htmlspecialchars($row['Strings']); ?></td> <!-- Показване на колоната "Стрингове" -->
                        <td><?php echo htmlspecialchars($row['PanelCount']); ?></td>
                        <td><?php echo htmlspecialchars($row['UsageType']); ?></td>
                        <td><?php echo htmlspecialchars($row['ZeroExportSetting']); ?></td>
                        <td class="alarm-cell"><?php echo htmlspecialchars($row['InvAlarms']); ?></td> <!-- Аларми с червен болд -->
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
