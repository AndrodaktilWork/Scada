<?php
include 'db_connection.php';

try {
    // Изключване на проверката за външни ключове
    $conn->exec("SET FOREIGN_KEY_CHECKS=0");

    // Изчистване на данни в зависимите таблици
    $conn->exec("DELETE FROM InverterData");
    $conn->exec("DELETE FROM StringBoxData");
    $conn->exec("DELETE FROM StringBoxes");
    $conn->exec("DELETE FROM StringsByStringBox");
    $conn->exec("DELETE FROM StrinxBoxesByInverter");

    // Изчистване на данни в основните таблици
    $conn->exec("DELETE FROM Inverters");
    $conn->exec("DELETE FROM PVPlants");
    $conn->exec("DELETE FROM Customers");

    // Включване на проверката за външни ключове обратно
    $conn->exec("SET FOREIGN_KEY_CHECKS=1");

    // Функция за генериране на 10-цифрен телефонен номер
    function generatePhoneNumber() {
        return '08' . rand(10000000, 99999999); // Примерен български телефонен номер, започващ с '08'
    }

    // Добавяне на демо клиенти
    $customers = [];
    for ($i = 1; $i <= 10; $i++) {
        $customerName = "Клиент $i";
        $representative = "Представител $i";
        $primaryContact = generatePhoneNumber();  // Генериране на 10-цифрен телефонен номер

        $stmt = $conn->prepare("INSERT INTO Customers (CustomerName, Representative, PrimaryContact) VALUES (:customerName, :representative, :primaryContact)");
        $stmt->execute([
            ':customerName' => $customerName,
            ':representative' => $representative,
            ':primaryContact' => $primaryContact
        ]);

        $customers[$i] = $conn->lastInsertId();
    }

    // Добавяне на соларни централи за всеки клиент
    $plants = [];
    foreach ($customers as $customerId) {
        for ($j = 1; $j <= 2; $j++) {
            $plantName = "Централа $j на клиент $customerId";
            $address = "Адрес $j";
            $region = "Регион $j";
            $country = "България";

            $stmt = $conn->prepare("INSERT INTO PVPlants (PlantName, Address, Region, Country, Customers_CustomerID) VALUES (:plantName, :address, :region, :country, :customerId)");
            $stmt->execute([
                ':plantName' => $plantName,
                ':address' => $address,
                ':region' => $region,
                ':country' => $country,
                ':customerId' => $customerId
            ]);

            $plants[] = $conn->lastInsertId();
        }
    }

    // Добавяне на инвертори за всяка централа
    foreach ($plants as $plantId) {
        for ($k = 1; $k <= 5; $k++) {
            $inverterName = "Инвертор $k на централа $plantId";
            $inverterType = "Тип $k";
            $capacity = rand(100, 500) . " kW";

            $stmt = $conn->prepare("INSERT INTO Inverters (InverterName, InverterTypes_InverterType, Capacity, PVPlants_PlantID) VALUES (:inverterName, :inverterType, :capacity, :plantId)");
            $stmt->execute([
                ':inverterName' => $inverterName,
                ':inverterType' => $inverterType,
                ':capacity' => $capacity,
                ':plantId' => $plantId
            ]);

            // Добавяне на примерни данни за инвертора
            $inverterId = $conn->lastInsertId();
            $stmt = $conn->prepare("INSERT INTO InverterData (Inverters_InverterNr, ACVoltageL1, ACCurrentL1, DCVoltage, DCCurrent, InverterTemp, TotalPower, Time) VALUES (:inverterId, :acVoltageL1, :acCurrentL1, :dcVoltage, :dcCurrent, :inverterTemp, :totalPower, NOW())");
            $stmt->execute([
                ':inverterId' => $inverterId,
                ':acVoltageL1' => rand(220, 240),
                ':acCurrentL1' => rand(10, 20),
                ':dcVoltage' => rand(300, 400),
                ':dcCurrent' => rand(15, 30),
                ':inverterTemp' => rand(20, 40),
                ':totalPower' => rand(100, 500)
            ]);
        }
    }

    echo "Данните бяха успешно импортирани!";
} catch (PDOException $e) {
    // Включване на проверката за външни ключове обратно при грешка
    $conn->exec("SET FOREIGN_KEY_CHECKS=1");
    echo "Грешка: " . $e->getMessage();
}
?>
