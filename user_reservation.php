<?php
session_start();
require_once "config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$sql = "SELECT * FROM reservations WHERE user_id = :user_id ORDER BY date_reservation, heure_reservation";
$stmt = $pdo->prepare($sql);
$stmt->execute([":user_id" => $user_id]);
$reservations = $stmt->fetchAll();
?>

<h2>Mes réservations</h2>
<table border="1">
    <tr>
        <th>Date</th>
        <th>Heure</th>
        <th>Description</th>
        <th>Action</th>
    </tr>
    <?php foreach ($reservations as $res) : ?>
        <tr>
            <td><?= $res["date_reservation"] ?></td>
            <td><?= $res["heure_reservation"] ?></td>
            <td><?= $res["description"] ?></td>
            <td><a href="delete.php?id=<?= $res['id'] ?>">❌ Annuler</a></td>
        </tr>
    <?php endforeach; ?>
</table>

<a href="dashboard.php">Retour</a>
