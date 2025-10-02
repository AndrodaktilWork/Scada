<?php
include 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plantId = $_POST['plant_id'];
    $inverterName = $_POST['inverter_name'];
    $inverterType = $_POST['inverter_type'];

    try {
        // Вмъкване на нов инвертор в базата данни
        $stmt = $conn->prepare("INSERT INTO Inverters (InverterName, InverterTypes_InverterType, PVPlants_PlantID) VALUES (:name, :type, :plant_id)");
        $stmt->execute(['name' => $inverterName, 'type' => $inverterType, 'plant_id' => $plantId]);

        // Пренасочване обратно към редакцията на централата
        header("Location: edit_plant.php?id=" . htmlspecialchars($plantId));
        exit;
    } catch (PDOException $e) {
        echo "Грешка при добавяне на инвертор: " . htmlspecialchars($e->getMessage());
    }
}
?>
