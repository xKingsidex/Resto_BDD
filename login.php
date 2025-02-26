<?php
require_once "config.php";
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];

    $sql = "SELECT * FROM users WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([":email" => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user["password"])) {
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["nom"] = $user["nom"];
        header("Location: dashboard.php");
        exit;
    } else {
        $error_message = "Email ou mot de passe incorrect.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion - Le Gourmet Nomade</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..700&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
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
        .form-container {
            background-color: #000;
            border-radius: 20px;
        }
        .form-title {
            color: #c1a068;
            font-weight: 600;
        }
        .custom-input {
            background-color: rgba(255, 255, 255, 0.9);
            border: 1px solid #c1a068;
            padding: 10px 15px;
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
        .login-link {
            color: #c1a068;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .login-link:hover {
            color: #9a7d4e;
            text-decoration: underline;
        }
        .alert-custom {
            background-color: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            border: 1px solid #dc3545;
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

    <main class="min-vh-100 d-flex justify-content-center align-items-center py-5">
        <div class="container pt-5" >
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="text-center mb-5">
                            <h2 class="form-title">CONNEXION</h2>
                        </div>
                    <div class="form-container p-5 bg-dark bg-opacity-50">
                    
                        
                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-custom text-center mb-4" role="alert">
                                <?php echo $error_message; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="mb-4">
                            <div class="mb-4">
                                <div class="input-group">
                                    <span class="input-group-text bg-dark text-white border-0">
                                        <i class="fas fa-envelope" style="color: #c1a068;"></i>
                                    </span>
                                    <input type="email" name="email" class="form-control custom-input" placeholder="Email" required>
                                </div>
                            </div>
                            
                            <div class="mb-5">
                                <div class="input-group">
                                    <span class="input-group-text bg-dark text-white border-0">
                                        <i class="fas fa-lock" style="color: #c1a068;"></i>
                                    </span>
                                    <input type="password" name="password" class="form-control custom-input" placeholder="Mot de passe" required>
                                </div>
                            </div>
                            
                            <div class="text-center">
                                <button type="submit" class="btn btn-custom px-5 py-2">Se connecter</button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-4">
                            <p>Pas encore de compte ? <a href="register.php" class="login-link">S'inscrire</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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