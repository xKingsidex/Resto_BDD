<?php

session_start();
require_once "config.php";


$success_message = "";
$error_message = "";

// Génération du token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Vérification du token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = "Erreur de validation du formulaire. Veuillez réessayer.";
    } else {
        $nom = trim($_POST["nom"]);
        $prenom = trim($_POST["prenom"]);
        $email = trim($_POST["email"]);
        $password = $_POST["password"];
        $postal = trim($_POST["postal"]);
        $phone = trim($_POST["phone"]);
        
        // Convertir le format de date de JJ/MM/AAAA à YYYY-MM-DD
        $birthday = trim($_POST["birthday"]);
        if (!empty($birthday)) {
            // Diviser la date en jour, mois, année
            $date_parts = explode('/', $birthday);
            if (count($date_parts) == 3) {
                $jour = $date_parts[0];
                $mois = $date_parts[1];
                $annee = $date_parts[2];
                
                // Vérifier si la date est valide
                if (checkdate((int)$mois, (int)$jour, (int)$annee)) {
                    // Reformater au format YYYY-MM-DD pour MySQL
                    $birthday = "$annee-$mois-$jour";
                } else {
                    $error_message = "Date de naissance invalide. Utilisez le format JJ/MM/AAAA.";
                }
            } else {
                $error_message = "Format de date incorrect. Utilisez le format JJ/MM/AAAA (ex: 08/10/2004).";
            }
        }
        
        // Continuer uniquement s'il n'y a pas d'erreur de date
        if (empty($error_message)) {
            // Vérifier si l'email existe déjà
            $check_sql = "SELECT * FROM users WHERE email = :email";
            $check_stmt = $pdo->prepare($check_sql);
            $check_stmt->execute([":email" => $email]);
            
            if ($check_stmt->rowCount() > 0) {
                $error_message = "Cet email est déjà utilisé.";
            } else {
                // Générer un token unique
                $verification_token = bin2hex(random_bytes(32));
                
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO users (nom, prenom, email, password, code_postal, date_naissance, telephone, verification_token, email_verified) 
                        VALUES (:nom, :prenom, :email, :password, :postal, :birthday, :phone, :token, 0)";
                $stmt = $pdo->prepare($sql);
                
                try {
                    $stmt->execute([
                        ":nom" => $nom,
                        ":prenom" => $prenom,
                        ":email" => $email,
                        ":password" => $hashed_password,
                        ":postal" => $postal,
                        ":birthday" => $birthday,
                        ":phone" => $phone,
                        ":token" => $verification_token
                    ]);
                    
                    // Envoi de l'email de vérification
                    $to = $email;
                    $subject = "Vérification de votre compte - Le Gourmet Nomade";
                    $verification_link = "http://localhost/Resto_BDD/verify.php?token=" . $verification_token;
                    
                    // Configuration de l'envoi d'email avec PHPMailer
                    require 'PHPMailer/src/Exception.php';
                    require 'PHPMailer/src/PHPMailer.php';
                    require 'PHPMailer/src/SMTP.php';
                    
                    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                    try {
                        // Configuration du serveur
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com'; 
                        $mail->SMTPAuth = true;
                        $mail->Username = 'enzo.foulon53@gmail.com'; 
                        $mail->Password = 'gxgr wkqp wvnk wtby'; 
                        $mail->SMTPSecure = 'tls';
                        $mail->Port = 587;
                        $mail->SMTPOptions = array(
                            'ssl' => array(
                                'verify_peer' => false,
                                'verify_peer_name' => false,
                                'allow_self_signed' => true
                            ));
                        
                        // Destinataires
                        $mail->setFrom('enzo.foulon53@gmail.com', 'Le Gourmet Nomade');
                        $mail->addAddress($email);
                        
                        // Contenu
                        $mail->isHTML(true);
                        $mail->Subject = $subject;
                        $mail->Body = "
                            <html>
                            <head>
                                <style>
                                    body { font-family: Arial, sans-serif; }
                                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                                    .header { background-color: #c1a068; color: white; padding: 10px; text-align: center; }
                                    .content { padding: 20px; }
                                    .button { background-color: #c1a068; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
                                </style>
                            </head>
                            <body>
                                <div class='container'>
                                    <div class='header'>
                                        <h2>Bienvenue chez Le Gourmet Nomade</h2>
                                    </div>
                                    <div class='content'>
                                        <p>Bonjour $prenom $nom,</p>
                                        <p>Merci pour votre inscription. Pour activer votre compte, veuillez cliquer sur le lien ci-dessous :</p>
                                        <p style='text-align: center;'><a href='$verification_link' class='button'>Vérifier mon compte</a></p>
                                        <p>Si le bouton ne fonctionne pas, copiez et collez ce lien dans votre navigateur :</p>
                                        <p>$verification_link</p>
                                        <p>À bientôt !</p>
                                        <p>L'équipe du Gourmet Nomade</p>
                                    </div>
                                </div>
                            </body>
                            </html>
                        ";
                        
                        $mail->send();
                        $success_message = "Inscription réussie ! Un email de vérification a été envoyé à votre adresse. Veuillez vérifier votre compte avant de vous connecter.";
                        
                        // Régénérer le token CSRF après une inscription réussie
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                        $csrf_token = $_SESSION['csrf_token'];
                    } catch (Exception $e) {
                        $error_message = "Erreur lors de l'envoi de l'email : " . $mail->ErrorInfo;
                    }
                } catch (PDOException $e) {
                    $error_message = "Erreur lors de l'inscription : " . $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inscription - Le Gourmet Nomade</title>
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
        h2 {
             margin-top: 50px; 
            text-align: center;
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
                    <a href="reservation.php" class="btn btn-custom ms-3">Réserver</a>
                </div>
            </div>
        </nav>
    </header>

    <main class="min-vh-100 d-flex justify-content-center align-items-center py-5">
        <div class="container pt-5">
            <div class="row justify-content-center">
                <div class="col-md-8">
                <div class="text-center mb-5">
                            <h2 class="form-title">INSCRIPTION</h2>
                        </div>
                    <div class="form-container bg-dark bg-opacity-50 p-5">
                        
                        
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success-custom text-center mb-4" role="alert">
                    <?php echo htmlspecialchars($success_message); ?> <a href="login.php" class="login-link fw-bold">Se connecter</a>
                    </div>
                        <?php endif; ?>

                        <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger-custom text-center mb-4" role="alert">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                    <?php endif; ?>

                        <form method="POST" class="mb-4">
                            <!-- Ajout du champ caché pour le token CSRF -->
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            
                            <div class="row mb-4">
                                <div class="col-md-6 mb-3 mb-md-0">
                                    <div class="input-group">
                                        <span class="input-group-text bg-dark text-white border-0">
                                            <i class="fas fa-user" style="color: #c1a068;"></i>
                                        </span>
                                        <input type="text" name="nom" class="form-control custom-input" placeholder="Nom" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text bg-dark text-white border-0">
                                            <i class="fas fa-user" style="color: #c1a068;"></i>
                                        </span>
                                        <input type="text" name="prenom" class="form-control custom-input" placeholder="Prénom" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <div class="col-md-6 mb-3 mb-md-0">
                                <div class="input-group">
                                    <span class="input-group-text bg-dark text-white border-0">
                                        <i class="fas fa-envelope" style="color: #c1a068;"></i>
                                    </span>
                                    <input type="email" name="email" class="form-control custom-input" placeholder="Email" required>
                                </div>
                                </div>
                                <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text bg-dark text-white border-0">
                                        <i class="fas fa-home" style="color: #c1a068;"></i>
                                    </span>
                                    <input type="postal" name="postal" class="form-control custom-input" placeholder="Code postal" required>
                                </div>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <div class="col-md-6 mb-3 mb-md-0">
                                <div class="input-group">
                                    <span class="input-group-text bg-dark text-white border-0">
                                        <i class="fas fa-cake-candles" style="color: #c1a068;"></i>
                                    </span>
                                    <input type="birthday" name="birthday" class="form-control custom-input" placeholder="08/10/2004" required>
                                </div>
                                </div>
                                <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text bg-dark text-white border-0">
                                        <i class="fas fa-phone" style="color: #c1a068;"></i>
                                    </span>
                                    <input type="phone" name="phone" class="form-control custom-input" placeholder="Télephone" required>
                                </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <div class="input-group">
                                    <span class="input-group-text bg-dark text-white border-0">
                                        <i class="fas fa-lock" style="color: #c1a068;"></i>
                                    </span>
                                    <input type="password" name="password" class="form-control custom-input" placeholder="Mot de passe" required>
                                </div>
                            </div>
                            
                            
                            <div class="text-center">
                                <button type="submit" class="btn btn-custom px-5 py-2">S'inscrire</button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-4">
                            <p>Déjà membre ? <a href="login.php" class="login-link">Se connecter</a></p>
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