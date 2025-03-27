<?php

$dsn = 'mysql:host=db;dbname=lb_pdo_rent;charset=utf8';
$username = 'user';
$password = 'password';
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    die("Помилка підключення до бази даних: " . $e->getMessage());
}

$date = $_GET['date'] ?? date('Y-m-d');
$vendor = $_GET['vendor'] ?? '';

// Отримання доходу з прокату на вибрану дату
$query_income = "SELECT SUM(Cost) AS total_income FROM rent WHERE Date_end <= :date";
$stmt_income = $pdo->prepare($query_income);
$stmt_income->execute(['date' => $date]);
$income = $stmt_income->fetch()['total_income'] ?? 0;

// Автомобілі обраного виробника
$query_cars_by_vendor = "SELECT cars.* FROM cars 
    JOIN vendors ON cars.FID_Vendors = vendors.ID_Vendors 
    WHERE vendors.Name = :vendor";
$stmt_cars_by_vendor = $pdo->prepare($query_cars_by_vendor);
$stmt_cars_by_vendor->execute(['vendor' => $vendor]);
$cars_by_vendor = $stmt_cars_by_vendor->fetchAll();

// Вільні автомобілі на обрану дату
$query_available_cars = "SELECT * FROM cars WHERE ID_Cars NOT IN (
    SELECT FID_Car FROM rent WHERE :date BETWEEN Date_start AND Date_end
)";
$stmt_available_cars = $pdo->prepare($query_available_cars);
$stmt_available_cars->execute(['date' => $date]);
$available_cars = $stmt_available_cars->fetchAll();

?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Автопрокат</title>
</head>
<body>
    <h1>Інформація про автопрокат</h1>
    <form method="GET">
        <label>Оберіть дату: <input type="date" name="date" value="<?= htmlspecialchars($date) ?>"></label>
        <label>Виробник: <input type="text" name="vendor" value="<?= htmlspecialchars($vendor) ?>"></label>
        <button type="submit">Фільтрувати</button>
    </form>

    <h2>Отриманий дохід станом на <?= htmlspecialchars($date) ?>:</h2>
    <p><?= number_format($income, 2) ?> грн</p>

    <h2>Автомобілі виробника "<?= htmlspecialchars($vendor) ?>":</h2>
    <ul>
        <?php foreach ($cars_by_vendor as $car): ?>
            <li><?= htmlspecialchars($car['Name']) ?> (<?= $car['Release_date'] ?>)</li>
        <?php endforeach; ?>
    </ul>

    <h2>Вільні автомобілі на <?= htmlspecialchars($date) ?>:</h2>
    <ul>
        <?php foreach ($available_cars as $car): ?>
            <li><?= htmlspecialchars($car['Name']) ?> (<?= $car['Release_date'] ?>)</li>
        <?php endforeach; ?>
    </ul>
</body>
</html>
