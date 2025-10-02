<?php
include 'db_connection.php';

$plantId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plantName = $_POST['plant_name'];
    $address = $_POST['address'];
    $country = $_POST['country'];
    $region = $_POST['region'];
    $meteoStation = $_POST['meteo_station'];

    try {
        $stmt = $conn->prepare("UPDATE PVPlants SET PlantName = :plantName, Address = :address, Country = :country, Region = :region, MeteoStations_StationNr = :meteoStation WHERE PlantID = :id");
        $stmt->execute([
            'plantName' => $plantName,
            'address' => $address,
            'country' => $country,
            'region' => $region,
            'meteoStation' => $meteoStation,
            'id' => $plantId
        ]);

        header("Location: manage_plants.php");
        exit;
    } catch (PDOException $e) {
        echo "Грешка при обновяване на централата: " . htmlspecialchars($e->getMessage());
    }
}

$stmt = $conn->prepare("SELECT * FROM PVPlants WHERE PlantID = :id");
$stmt->execute(['id' => $plantId]);
$plant = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$plant) {
    echo "Централата не е намерена!";
    exit;
}

// Извличане на всички метеостанции за dropdown менюто
$meteoStationsStmt = $conn->query("SELECT * FROM MeteoStations");
$meteoStations = $meteoStationsStmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактиране на централа</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
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

        .container {
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            max-width: 800px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        form input[type="text"], form select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        form input[type="submit"] {
            padding: 10px 20px;
            background-color: #1abc9c;
            border: none;
            border-radius: 4px;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        form input[type="submit"]:hover {
            background-color: #16a085;
        }
    </style>
</head>
<body>

<header>
    <h1>Редактиране на централа</h1>
</header>

<div class="container">
    <form action="edit_plant.php?id=<?php echo $plantId; ?>" method="post">
        <label for="plant_name">Име на централата</label>
        <input type="text" id="plant_name" name="plant_name" value="<?php echo htmlspecialchars($plant['PlantName']); ?>" required>

        <label for="address">Адрес</label>
        <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($plant['Address']); ?>" required>

        <label for="country">Държава</label>
        <input type="text" id="country" name="country" value="<?php echo htmlspecialchars($plant['Country']); ?>" required>

        <label for="region">Регион</label>
        <input type="text" id="region" name="region" value="<?php echo htmlspecialchars($plant['Region']); ?>" required>

        <label for="meteo_station">Метеостанция</label>
        <select id="meteo_station" name="meteo_station" required>
            <?php foreach ($meteoStations as $station): ?>
            <option value="<?php echo htmlspecialchars($station['StationNr']); ?>" <?php echo $station['StationNr'] == $plant['MeteoStations_StationNr'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($station['StationName']); ?>
            </option>
            <?php endforeach; ?>
        </select>

        <input type="submit" value="Запази промените">
    </form>
</div>

</body>
</html>
