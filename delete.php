<?php
session_start();
require_once "config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET["id"])) {
    $id = $_GET["id"];
    $user_id = $_SESSION["user_id"];

    $sql = "DELETE FROM reservations WHERE id = :id AND user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([":id" => $id, ":user_id" => $user_id]);

    echo "✅ Réservation annulée avec succès.";
    echo "<br><a href='mes_reservations.php'>Retour</a>";
}
?>
