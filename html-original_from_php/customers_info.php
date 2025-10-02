<?php
// Включване на връзката към базата данни
include 'db_connection.php';

// Извличане на всички клиенти от базата данни
$stmt = $conn->prepare("SELECT * FROM Customers"); // Подготвяме SQL заявка за извличане на всички клиенти
$stmt->execute(); // Изпълняваме заявката
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC); // Получаваме всички резултати като асоциативен масив
?>

<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers Information</title>
    <style>
        /* Основни стилове за тялото на страницата */
        body {
            font-family: 'Verdana', sans-serif; /* Основен шрифт за страницата */
            background-color: #f4f4f9; /* Цветът на фона на страницата */
            color: #333; /* Основен цвят на текста */
            margin: 0; /* Премахване на стандартните отстояния */
            padding: 0; /* Премахване на стандартните отстояния */
            display: flex; /* Използваме флексбокс за подредба на съдържанието */
            flex-direction: column; /* Подреждаме елементите вертикално */
        }

        /* Стилове за хедъра */
        header {
            background-color: #34495e; /* Тъмен фон за заглавката */
            color: white; /* Бял текст */
            padding: 1px; /* Вътрешно отстояние на заглавката */
            text-align: center; /* Центриране на текста */
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); /* Леко сенчест ефект */
            font-size: 10px; /* Размер на шрифта за заглавката */
        }

        /* Стилове за страничното меню (sidebar) */
        nav {
            background-color: #2c3e50; /* Тъмен фон за навигационната лента */
            padding: 15px; /* Вътрешно отстояние на навигацията */
            width: 250px; /* Фиксирана ширина на страничното меню */
            box-sizing: border-box; /* Включване на padding в ширината */
            position: fixed; /* Фиксиране на позицията на страничното меню */
            top: 0; /* Позициониране най-горе на страницата */
            left: 0; /* Позициониране най-вляво */
            height: 100%; /* Височина, покриваща целия екран */
            z-index: 1000; /* Позициониране над другите елементи */
        }

        /* Стилове за списъка в навигацията */
        nav ul {
            list-style-type: none; /* Премахване на стандартните списъчни маркери */
            padding: 0; /* Премахване на вътрешното отстояние на списъка */
        }

        /* Стилове за всеки елемент от списъка в навигацията */
        nav ul li {
            margin-bottom: 15px; /* Отстояние между елементите в списъка */
        }

        /* Стилове за линковете в навигацията */
        nav ul li a {
            color: white; /* Бял цвят на текста на линковете */
            text-decoration: none; /* Премахване на подчертаването на линковете */
            font-weight: bold; /* Удебелен шрифт */
            display: block; /* Линковете заемат цялото пространство на родителя */
            padding: 10px 15px; /* Вътрешно отстояние на линковете */
            border-radius: 5px; /* Заобляне на ъглите на линковете */
            background-color: #34495e; /* Фон за линковете */
            transition: background-color 0.3s; /* Плавен преход на фона при hover */
        }

        /* Промяна на фона на линковете при задържане на мишката */
        nav ul li a:hover {
            background-color: #1abc9c; /* Нов цвят на фона при hover */
        }

        /* Основен контейнер за съдържанието на страницата */
        .container {
            margin-left: 270px; /* Отстояние, за да се освободи място за страничното меню */
            padding: 20px; /* Вътрешно отстояние */
            flex: 1; /* Контейнерът заема останалото пространство */
            max-width: calc(100% - 270px); /* Максимална ширина на контейнера, след страничното меню */
            box-sizing: border-box; /* Включване на padding в ширината */
        }

        /* Основен стил за съдържанието */
        .content {
            padding: 20px; /* Вътрешно отстояние на съдържанието */
            background-color: white; /* Бял фон за съдържанието */
            border-radius: 8px; /* Заобляне на ъглите */
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1); /* Леко сенчест ефект */
        }

        /* Стил за заглавния бокс */
        .title-box {
            background-color: #2c7c31; /* Тъмнозелен фон */
            color: white; /* Бял текст */
            padding: 1px; /* Вътрешно отстояние */
            border-radius: 8px; /* Заобляне на ъглите */
            text-align: center; /* Центриране на текста */
            margin-bottom: 20px; /* Отстояние под заглавния бокс */
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); /* Леко сенчест ефект */
            max-width: 100%; /* Ширина на заглавния бокс */
            font-weight: normal; /* Премахване на удебеляването на текста */
        }

        /* Стилове за заглавието в заглавния бокс */
        h2 {
            color: white; /* Бял цвят на текста */
            text-align: center; /* Центриране на текста */
            font-size: 20px; /* Размер на шрифта */
            margin-bottom: 20px; /* Отстояние под заглавието */
        }

        /* Основен контейнер за клиентските боксове */
        .clients-container {
            display: flex; /* Използваме флексбокс за подредба на клиентските боксове */
            flex-wrap: wrap; /* Позволяваме пренасяне на нов ред при нужда */
            justify-content: space-between; /* Равномерно разпределяне на клиентските боксове */
            gap: 30px; /* Отстояние между клиентските боксове */
        }

        /* Основен стил за всеки клиентски бокс */
        .client-box {
            display: flex; /* Използваме флексбокс за подредба на съдържанието */
            flex-direction: row; /* Подреждаме елементите хоризонтално */
            width: calc(33.333% - 20px); /* Ширина на всеки бокс, за да има три на ред */
            box-sizing: border-box; /* Включваме padding и border в общата ширина */
            margin-bottom: 30px; /* Отстояние между редовете */
            padding: 20px; /* Вътрешно отстояние на съдържанието */
            border: 1px solid #ddd; /* Светлосива рамка около бокса */
            border-radius: 8px; /* Заобляне на ъглите на бокса */
            background-color: #ffecd2; /* Светлооранжев фон на бокса */
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); /* Леко сенчест ефект за дълбочина */
            transition: transform 0.3s, box-shadow 0.3s; /* Плавен преход при задържане на мишката */
        }

        /* Промяна на стила при hover върху клиентския бокс */
        .client-box:hover {
            transform: translateY(-5px); /* Леко повдигане на бокса при hover */
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.2); /* По-силна сянка при hover */
        }

        /* Ляв контейнер за клиентската информация */
        .left-content {
            flex: 50%; /* Задаваме 50% от ширината на основния контейнер */
            padding-right: 20px; /* Добавяме разстояние между левия и десния контейнер */
            box-sizing: border-box; /* Включваме padding в ширината на контейнера */
        }

        /* Десен контейнер за логото и името на фирмата */
        .right-content {
            flex: 50%; /* Задаваме 50% от ширината на основния контейнер */
            display: flex; /* Използваме флексбокс за центриране на съдържанието */
            flex-direction: column; /* Подреждаме елементите вертикално */
            align-items: center; /* Центрираме елементите хоризонтално */
            justify-content: center; /* Центрираме елементите вертикално */
            box-sizing: border-box; /* Включваме padding в ширината на контейнера */
        }

        /* Контейнер за логото */
        .logo-container {
            width: 80px; /* Фиксирана ширина на контейнера за логото */
            height: 80px; /* Фиксирана височина на контейнера за логото */
            margin-bottom: 10px; /* Отстояние под логото */
            border: 2px solid #ddd; /* Светлосива рамка около логото */
            border-radius: 50%; /* Заобляне на логото, за да изглежда кръгло */
            overflow: hidden; /* Скриваме частите на изображението извън контейнера */
        }

        /* Стил за логото вътре в контейнера */
        .logo-container img {
            width: 100%; /* Логото запълва цялата ширина на контейнера */
            height: 100%; /* Логото запълва цялата височина на контейнера */
            object-fit: cover; /* Изображението ще се мащабира, за да запълни контейнера */
        }

        /* Стил за името на фирмата */
        .company-name {
            font-weight: bold; /* Удебелен шрифт за името на фирмата */
            text-align: center; /* Центриране на текста */
            margin-top: 10px; /* Отстояние над името на фирмата */
        }

        /* Стил за всяко поле в лявата част (клиентска информация) */
        .field-box {
            margin-top: 20px; /* Отстояние над всяко поле */
            padding: 15px; /* Вътрешно отстояние на полето */
            border: 1px solid #ccc; /* Светлосива рамка около полето */
            border-radius: 5px; /* Заобляне на ъглите на полето */
            background-color: #e0f7fa; /* Светъл фон на полето */
            transition: background-color 0.3s; /* Плавен преход на фона при hover */
        }

        /* Промяна на фона на полето при задържане на мишката */
        .field-box:hover {
            background-color: #b2ebf2; /* Промяна на фона на полето при hover */
        }

        /* Стил за инверторната информация в лявата част */
        .inverter-box {
            margin-top: 15px; /* Отстояние над инверторната кутия */
            padding: 10px; /* Вътрешно отстояние на инверторната кутия */
            border: 1px solid #bbb; /* Светлосива рамка около инверторната кутия */
            border-radius: 5px; /* Заобляне на ъглите на инверторната кутия */
            background-color: #f0f0f0; /* Светъл фон на инверторната кутия */
            transition: background-color 0.3s, box-shadow 0.3s; /* Плавен преход на фона и сенките при hover */
        }

        /* Промяна на стила при hover върху инверторната кутия */
        .inverter-box:hover {
            background-color: #e0e0e0; /* Промяна на фона на инверторната кутия при hover */
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); /* Усилване на сенчестия ефект при hover */
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
            <div class="title-box">
                <h2>Информация за клиентите</h2>
            </div>

            <div class="clients-container">
                <!-- Генериране на клиентските боксове от базата данни -->
                <?php foreach ($customers as $row): ?>
                <div class="client-box">
                    <!-- Ляв контейнер с информация за клиента -->
                    <div class="left-content">
                        <h3>Данни за клиент:</h3>
                        <div class="field-box">
                            <strong>Представител:</strong> <?php echo htmlspecialchars($row['Representative']); ?>
                        </div>
                        <div class="field-box">
                            <strong>Телефон:</strong> <?php echo htmlspecialchars($row['PrimaryContact']); ?>
                        </div>
                        <div class="field-box">
                            <strong>Търговец:</strong> <?php echo htmlspecialchars($row['Salesperson']); ?>
                        </div>
                        <div class="field-box">
                            <strong>Емейл:</strong> <?php echo htmlspecialchars($row['Email']); ?>
                        </div>
                        <div class="inverter-box">
                            <strong>Информация за инвертор:</strong> Налична информация...
                        </div>
                    </div>

                    <!-- Десен контейнер с логото и името на фирмата -->
                    <div class="right-content">
                        <div class="logo-container">
                            <img src="https://uxwing.com/wp-content/themes/uxwing/download/peoples-avatars/man-user-circle-icon.png" alt="Лого на фирмата"> <!-- Временно лого -->
                        </div>
                        <div class="company-name">
                            <?php echo htmlspecialchars($row['CustomerName']); ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>
