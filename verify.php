<?php
require_once "config.php";

$token = isset($_GET['token']) ? $_GET['token'] : '';
$message = '';

if (!empty($token)) {
    // Vérifier si le token existe dans la base de données
    $sql = "SELECT * FROM users WHERE verification_token = :token AND email_verified = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':token' => $token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Mettre à jour l'utilisateur comme vérifié
        $update_sql = "UPDATE users SET email_verified = 1, verification_token = NULL WHERE id = :id";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->execute([':id' => $user['id']]);
        
        $message = "<div class='alert alert-success'>✅ Votre compte a été vérifié avec succès ! Vous pouvez maintenant vous connecter.</div>";
    } else {
        $message = "<div class='alert alert-danger'>⚠️ Lien de vérification invalide ou déjà utilisé.</div>";
    }
} else {
    $message = "<div class='alert alert-danger'>⚠️ Aucun token de vérification fourni.</div>";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vérification du compte - Le Gourmet Nomade</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..700&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .container-verify {
            background-color: rgba(0, 0, 0, 0.5);
            border-radius: 10px;
            padding: 30px;
            margin-top: 50px;
        }
        .btn-reservation {
            background-color: #c1a068;
            color: #1e1e1e;
            font-weight: 600;
            padding: 10px 25px;
            border: none;
            transition: all 0.3s ease;
        }
        .btn-reservation:hover {
            background-color: #9a7d4e;
            color: white;
        }
    </style>
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
                    <a href="login.php" class="btn btn-reservation ms-3">Connexion</a>
                </div>
            </div>
        </nav>
    </header>

    <main class="pt-5">
        <section id="verification" class="min-vh-100 d-flex justify-content-center align-items-center flex-column text-center py-5">
            <p class="fs-1 mt-5" style="color: #c1a068;">VÉRIFICATION DE COMPTE</p>
            <div class="container flex-column align-items-center">
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <?php echo $message; ?>
                        
                        <div class="p-4 rounded-3 bg-dark bg-opacity-50">
                            <div class="text-center mt-4 mb-4">
                                <?php if (strpos($message, 'success') !== false): ?>
                                    <i class="fas fa-check-circle fa-4x mb-3" style="color: #28a745;"></i>
                                <?php else: ?>
                                    <i class="fas fa-times-circle fa-4x mb-3" style="color: #dc3545;"></i>
                                <?php endif; ?>
                                
                                <div class="mt-4">
                                    <a href="login.php" class="btn btn-reservation">Se connecter</a>
                                    <a href="site.php" class="btn btn-outline-light ms-2">Retour à l'accueil</a>
                                </div>
                            </div>
                        </div>
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