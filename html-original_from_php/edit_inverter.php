<?php
include 'db_connection.php';

$inverterId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inverterName = $_POST['inverter_name'];
    $inverterType = $_POST['inverter_type'];

    try {
        $stmt = $conn->prepare("UPDATE Inverters SET InverterName = :name, InverterTypes_InverterType = :type WHERE InverterNr = :id");
        $stmt->execute(['name' => $inverterName, 'type' => $inverterType, 'id' => $inverterId]);

        header("Location: manage_inverters.php");
        exit;
    } catch (PDOException $e) {
        echo "Грешка при актуализиране на инвертора: " . htmlspecialchars($e->getMessage());
    }
}

$stmt = $conn->prepare("SELECT * FROM Inverters WHERE InverterNr = :id");
$stmt->execute(['id' => $inverterId]);
$inverter = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$inverter) {
    echo "Инверторът не е намерен!";
    exit;
}
?>

<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактиране на инвертор</title>
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
    <h1>Редактиране на инвертор</h1>
</header>

<div class="container">
    <form action="edit_inverter.php?id=<?php echo $inverterId; ?>" method="post">
        <label for="inverter_name">Име на инвертора</label>
        <input type="text" id="inverter_name" name="inverter_name" value="<?php echo htmlspecialchars($inverter['InverterName']); ?>" required>

        <label for="inverter_type">Тип на инвертора</label>
        <input type="text" id="inverter_type" name="inverter_type" value="<?php echo htmlspecialchars($inverter['InverterTypes_InverterType']); ?>" required>

        <input type="submit" value="Запази промените">
    </form>
</div>

</body>
</html>
