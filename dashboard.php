<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}
?>

<h1>Bienvenue, <?php echo $_SESSION["nom"]; ?> !</h1>
<a href="logout.php">Se déconnecter</a>
