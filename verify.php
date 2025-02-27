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
        
        $message = "Votre compte a été vérifié avec succès ! Vous pouvez maintenant vous connecter.";
        $message_type = "success";
    } else {
        $message = "Lien de vérification invalide ou déjà utilisé.";
        $message_type = "danger";
    }
} else {
    $message = "Aucun token de vérification fourni.";
    $message_type = "danger";
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            background-color: #333438;
            color: white;
            font-family: 'Lora', serif;
        }
        .navbar {
            background-color: #333438;
        }
        .container-verify {
            background-color: rgba(0, 0, 0, 0.5);
            border-radius: 20px;
            padding: 30px;
            margin-top: 150px;
        }
        .btn-custom {
            background-color: #c1a068;
            color: #333438;
            font-weight: 600;
            padding: 10px 25px;
            border: none;
            transition: all 0.3s ease;
        }
        .btn-custom:hover {
            background-color: #9a7d4e;
            color: white;
        }
        .message-success {
            color: #28a745;
        }
        .message-danger {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg fixed-top">
            <div class="container">
                <a class="navbar-brand" href="site.php#accueil">
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
                    <a href="site.php#reservation" class="btn btn-custom ms-3">Réserver</a>
                </div>
            </div>
        </nav>
    </header>

    <main class="min-vh-100">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="container-verify text-center">
                        <h2 style="color: #c1a068;">Vérification de compte</h2>
                        <div class="mt-4 mb-4">
                            <?php if ($message_type == "success"): ?>
                                <i class="fas fa-check-circle fa-4x mb-3" style="color: #28a745;"></i>
                                <p class="message-success"><?php echo $message; ?></p>
                            <?php else: ?>
                                <i class="fas fa-times-circle fa-4x mb-3" style="color: #dc3545;"></i>
                                <p class="message-danger"><?php echo $message; ?></p>
                            <?php endif; ?>
                        </div>
                        <a href="login.php" class="btn btn-custom">Se connecter</a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="bg-dark text-white py-5 mt-5">
        <!-- Votre footer actuel -->
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>