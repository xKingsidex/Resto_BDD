<?php
session_start();
// Vérifier si le fichier config.php existe et peut être inclus
if(file_exists("config.php")) {
    require_once "config.php";
    $db_connected = true;
} else {
    $db_connected = false;
}

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$message = "";
$user_logged_in = isset($_SESSION["user_id"]);

// Informations utilisateur par défaut
$user_info = [];

if ($user_logged_in && $db_connected) {
    try {
        // Récupérer la structure de la table users pour déterminer quelles colonnes existent
        $sql = "SHOW COLUMNS FROM users";
        $stmt = $pdo->query($sql);
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Construire une requête qui ne demande que les colonnes existantes
        $select_fields = [];
        $display_fields = [];
        
        // Chercher des colonnes utiles pour l'affichage dans l'ordre de préférence
        $possible_name_fields = ['username', 'nom', 'name', 'user_name', 'login', 'email'];
        $possible_phone_fields = ['telephone', 'phone', 'tel', 'mobile', 'portable', 'num_tel'];
        
        foreach ($possible_name_fields as $field) {
            if (in_array($field, $columns)) {
                $select_fields[] = $field;
                $display_fields[] = $field;
                break;
            }
        }
        
        foreach ($possible_phone_fields as $field) {
            if (in_array($field, $columns)) {
                $select_fields[] = $field;
                $display_fields[] = $field;
                break;
            }
        }
        
        if (empty($select_fields)) {
            // Si aucun champ spécifique n'est trouvé, sélectionner toutes les colonnes sauf id
            $sql = "SELECT * FROM users WHERE id = :user_id";
        } else {
            $sql = "SELECT " . implode(", ", $select_fields) . " FROM users WHERE id = :user_id";
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([":user_id" => $_SESSION["user_id"]]);
        $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user_info) {
            $user_info = [];
        }
    } catch (Exception $e) {
        $message = "<div class='alert alert-danger'>Erreur lors de la récupération des données utilisateur: " . $e->getMessage() . "</div>";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $user_logged_in && $db_connected) {
    try {
        $date = $_POST["date"] ?? '';
        $heure = $_POST["heure"] ?? '';
        $description = $_POST["description"] ?? '';
        $user_id = $_SESSION["user_id"];

        if (empty($date) || empty($heure)) {
            $message = "<div class='alert alert-danger'>Veuillez remplir tous les champs obligatoires.</div>";
        } else {
            // Vérifier si le créneau est disponible
            $sql = "SELECT * FROM reservations WHERE date_reservation = :date AND heure_reservation = :heure";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([":date" => $date, ":heure" => $heure]);
            
            if ($stmt->rowCount() > 0) {
                $message = "<div class='alert alert-danger'>⚠️ Ce créneau est déjà réservé. Choisissez un autre horaire.</div>";
            } else {
                // Insérer la réservation
                $sql = "INSERT INTO reservations (user_id, date_reservation, heure_reservation, description) VALUES (:user_id, :date, :heure, :description)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ":user_id" => $user_id,
                    ":date" => $date,
                    ":heure" => $heure,
                    ":description" => $description
                ]);
                $message = "<div class='alert alert-success'>✅ Réservation enregistrée avec succès !</div>";
            }
        }
    } catch (Exception $e) {
        $message = "<div class='alert alert-danger'>Erreur lors de la réservation: " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Réservation - Le Gourmet Nomade</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..700&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg fixed-top">
            <div class="container">
                <a class="navbar-brand" href="site.php">
                    <img src="assets/img/logoResto.png" alt="Logo" width="120" height="120" class="d-inline-block align-text-top rounded">
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item"><a class="nav-link" href="site.php#photos">Photos</a></li>
                        <li class="nav-item"><a class="nav-link" href="site.php#menu">Menu</a></li>
                        <li class="nav-item"><a class="nav-link" href="site.php#about">À Propos</a></li>
                    </ul>
                    <?php if ($user_logged_in): ?>
                        <a href="dashboard.php" class="btn btn-reservation ms-3">Mon Compte</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-reservation ms-3">Connexion</a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
    </header>

    <main class="pt-5">
        <section id="reservation" class="min-vh-100 d-flex justify-content-center align-items-center flex-column text-center py-5">
            <p class="fs-1 mb-5" style="color: #c1a068;">RESERVATION</p>
            <div class="container flex-column align-items-center">
                <div class="row justify-content-center">
                    <div class="col-md-8  ">
                        <?php echo $message; ?>
                        
                        <?php if (!$user_logged_in): ?>
                            <div class="alert alert-warning">
                                Vous devez être connecté ou créer un compte pour effectuer une réservation.
                            </div>
                            <div class="alert alert-warning button">
                                <a href="login.php" class="btn btn-reservation">Se connecter</a>
                                <a href="register.php" class="btn btn-reservation">S'inscrire</a>
                            </div>
                            
                        <?php elseif (!$db_connected): ?>
                            <div class="alert alert-danger">
                                Problème de connexion à la base de données. Veuillez contacter l'administrateur.
                            </div>
                        <?php else: ?>
                            <div class="p-4 rounded-3 bg-dark bg-opacity-50">
                                <form method="POST" action="reservation.php">
                                    <?php if (!empty($user_info)): ?>
                                        <?php foreach ($user_info as $field => $value): ?>
                                            <div class="input-group mb-5">
                                                <span class="input-group-text"><?php echo ucfirst($field); ?></span>
                                                <input type="text" class="form-control custom-input" value="<?php echo htmlspecialchars($value); ?>" disabled>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    
                                    <div class="input-group mb-5">
                                        <span class="input-group-text">Date</span>
                                        <input type="date" name="date" class="form-control custom-input" required>
                                    </div>
                                    
                                    <div class="input-group mb-5">
                                        <span class="input-group-text">Heure</span>
                                        <input type="time" name="heure" class="form-control custom-input" required>
                                    </div>
                                    
                                    <div class="input-group mb-5 w-100">
                                        <span class="input-group-text">Description</span>
                                        <textarea name="description" class="form-control custom-input" aria-label="With textarea"></textarea>
                                    </div>
                                    
                                    <div class="text-center">
                                        <button type="submit" class="btn btn-reservation">Réserver</button>
                                        <a href="dashboard.php" class="btn btn-reservation ms-2">Mon Compte</a>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-4 mb-4">
                    <h5 class="text-uppercase mb-3">Contact</h5>
                    <div class="d-flex flex-column align-items-center">
                        <i class="fas fa-phone fa-2x mb-2" style="color: #c1a068;"></i>
                        <p class="fs-6">+33 1 23 45 67 89</p>
                        <i class="fas fa-envelope fa-2x mb-2" style="color: #c1a068;"></i>
                        <p class="fs-6">contact@legourmetnomade.fr</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <h5 class="text-uppercase mb-3">Informations</h5>
                    <div class="d-flex flex-column align-items-center">
                        <i class="fas fa-map-marker-alt fa-2x mb-2" style="color: #c1a068;"></i>
                        <p class="fs-6">123 rue de la Gastronomie, Paris</p>
                        <i class="fas fa-clock fa-2x mb-2" style="color: #c1a068;"></i>
                        <p class="fs-6">Mar-Dim: 12h-14h30 / 19h-22h30</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <h5 class="text-uppercase mb-3">Réseaux</h5>
                    <div class="d-flex justify-content-center gap-3">
                        <i class="fab fa-facebook fa-2x" style="color: #c1a068;"></i>
                        <i class="fab fa-instagram fa-2x" style="color: #c1a068;"></i>
                        <i class="fab fa-twitter fa-2x" style="color: #c1a068;"></i>
                    </div>
                </div>
            </div>
            <hr class="bg-light my-4">
            <div class="text-center">
                <p class="mb-0 fs-6">&copy; 2025 Le Gourmet Nomade. Tous droits réservés.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>