<?php
$host = "localhost";
$dbname = "reservation_db"; // Nom de ta base
$username = "root"; // Par défaut sous XAMPP
$password = ""; // Vide sous XAMPP

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>
