<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// Génération d'un token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Configuration de la base de données
function connectDB() {
    $host = 'localhost'; // Remplacez par votre hôte
    $dbname = 'reservation_db'; // Remplacez par le nom de votre base de données
    $username = 'root'; // Remplacez par votre nom d'utilisateur
    $password = ''; // Remplacez par votre mot de passe
    
    try {
        $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    } catch(PDOException $e) {
        die("Erreur de connexion à la base de données: " . $e->getMessage());
    }
}

// Fonction pour récupérer les rendez-vous de l'utilisateur depuis la base de données
function getUserAppointments($userId) {
    try {
        $db = connectDB();
        $query = $db->prepare("SELECT * FROM reservations WHERE user_id = :user_id ORDER BY date ASC");
        $query->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $query->execute();
        
        return $query->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        // En cas d'erreur, on retourne un tableau vide
        error_log("Erreur lors de la récupération des rendez-vous: " . $e->getMessage());
        return [];
    }
}

// Récupération des rendez-vous de l'utilisateur
$appointments = getUserAppointments($_SESSION["user_id"]);

// Fonction pour récupérer les informations complètes du profil utilisateur
function getUserProfile($userId) {
    try {
        $db = connectDB();
        $query = $db->prepare("SELECT * FROM users WHERE id = :user_id");
        $query->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $query->execute();
        
        $userProfile = $query->fetch(PDO::FETCH_ASSOC);
        
        // Si aucun résultat, on utilise les données de session comme fallback
        if (!$userProfile) {
            return [
                'id' => $userId,
                'nom' => $_SESSION["nom"] ?? "Nom",
                'prenom' => $_SESSION["prenom"] ?? "Prénom",
                'email' => $_SESSION["email"] ?? "email@exemple.com",
                'telephone' => $_SESSION["telephone"] ?? "06 12 34 56 78",
                'date_naissance' => $_SESSION["date_naissance"] ?? "1980-01-01",
                'code_postal' => $_SESSION["code_postal"] ?? "75000"
            ];
        }
        
        return $userProfile;
    } catch(PDOException $e) {
        // En cas d'erreur, on retourne les données de session comme fallback
        error_log("Erreur lors de la récupération du profil: " . $e->getMessage());
        return [
            'id' => $userId,
            'nom' => $_SESSION["nom"] ?? "Nom",
            'prenom' => $_SESSION["prenom"] ?? "Prénom",
            'email' => $_SESSION["email"] ?? "email@exemple.com",
            'telephone' => $_SESSION["telephone"] ?? "06 12 34 56 78",
            'date_naissance' => $_SESSION["date_naissance"] ?? "1980-01-01",
            'code_postal' => $_SESSION["code_postal"] ?? "75000"
        ];
    }
}

// Fonction pour mettre à jour le profil utilisateur dans la base de données
function updateUserProfile($userId, $nom, $prenom, $email, $telephone, $date_naissance, $code_postal) {
    try {
        $db = connectDB();
        $query = $db->prepare("
            UPDATE users 
            SET nom = :nom, prenom = :prenom, email = :email, 
                telephone = :telephone, date_naissance = :date_naissance, code_postal = :code_postal 
            WHERE id = :user_id
        ");
        
        $query->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $query->bindParam(':nom', $nom, PDO::PARAM_STR);
        $query->bindParam(':prenom', $prenom, PDO::PARAM_STR);
        $query->bindParam(':email', $email, PDO::PARAM_STR);
        $query->bindParam(':telephone', $telephone, PDO::PARAM_STR);
        $query->bindParam(':date_naissance', $date_naissance, PDO::PARAM_STR);
        $query->bindParam(':code_postal', $code_postal, PDO::PARAM_STR);
        
        return $query->execute();
    } catch(PDOException $e) {
        error_log("Erreur lors de la mise à jour du profil: " . $e->getMessage());
        return false;
    }
}
// Fonction pour supprimer un compte utilisateur
function deleteUserAccount($userId) {
    try {
        $db = connectDB();
        
        // Commencer une transaction pour s'assurer que toutes les suppressions sont effectuées ou aucune
        $db->beginTransaction();
        
        // D'abord supprimer les réservations de l'utilisateur (pour respecter l'intégrité référentielle)
        $deleteReservations = $db->prepare("DELETE FROM reservations WHERE user_id = :user_id");
        $deleteReservations->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $deleteReservations->execute();
        
        // Ensuite supprimer le compte utilisateur
        $deleteUser = $db->prepare("DELETE FROM users WHERE id = :user_id");
        $deleteUser->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $deleteUser->execute();
        
        // Valider la transaction
        $db->commit();
        
        return true;
    } catch(PDOException $e) {
        // En cas d'erreur, annuler toutes les modifications
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log("Erreur lors de la suppression du compte: " . $e->getMessage());
        return false;
    }
}


// Récupération du profil utilisateur
$userProfile = getUserProfile($_SESSION["user_id"]);

// Traitement du formulaire de modification du profil
$profileUpdateMessage = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    // Vérification du token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $profileUpdateMessage = '<div class="alert alert-danger">Erreur de sécurité. Veuillez réessayer.</div>';
    } else {
        // Validation et nettoyage des données
        $nom = htmlspecialchars(trim($_POST['nom']), ENT_QUOTES, 'UTF-8');
        $prenom = htmlspecialchars(trim($_POST['prenom']), ENT_QUOTES, 'UTF-8');
        $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL) ? trim($_POST['email']) : '';
        $telephone = htmlspecialchars(trim($_POST['telephone']), ENT_QUOTES, 'UTF-8');
        $date_naissance = htmlspecialchars(trim($_POST['date_naissance']), ENT_QUOTES, 'UTF-8');
        $code_postal = htmlspecialchars(trim($_POST['code_postal']), ENT_QUOTES, 'UTF-8');
        
        if (empty($email)) {
            $profileUpdateMessage = '<div class="alert alert-danger">Adresse email invalide.</div>';
        } else {
            // Mise à jour dans la base de données
            $updateSuccess = updateUserProfile($_SESSION["user_id"], $nom, $prenom, $email, $telephone, $date_naissance, $code_postal);
            
            if ($updateSuccess) {
                // Mise à jour des informations de session
                $_SESSION["nom"] = $nom;
                $_SESSION["prenom"] = $prenom;
                $_SESSION["email"] = $email;
                $_SESSION["telephone"] = $telephone;
                $_SESSION["date_naissance"] = $date_naissance;
                $_SESSION["code_postal"] = $code_postal;
                
                // Rafraîchir les données du profil
                $userProfile = getUserProfile($_SESSION["user_id"]);
                
                $profileUpdateMessage = '<div class="alert alert-success">Votre profil a été mis à jour avec succès.</div>';
            } else {
                $profileUpdateMessage = '<div class="alert alert-danger">Une erreur est survenue lors de la mise à jour du profil. Veuillez réessayer.</div>';
            }
        }
    }
}
// Traitement de la demande de suppression de compte
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_account'])) {
    // Vérification du token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $accountDeleteMessage = '<div class="alert alert-danger">Erreur de sécurité. Veuillez réessayer.</div>';
    } else {
        // Suppression du compte
        $deleteSuccess = deleteUserAccount($_SESSION["user_id"]);
        
        if ($deleteSuccess) {
            // Détruire la session et rediriger vers la page d'inscription
            session_destroy();
            header("Location: register.php?deleted=1");
            exit();
        }
        
        else {
            $accountDeleteMessage = '<div class="alert alert-danger">Une erreur est survenue lors de la suppression du compte. Veuillez réessayer.</div>';
        }
    }
}

// Fonction pour générer un nom d'utilisateur complet en échappant les caractères spéciaux
function getFullName($userProfile) {
    $nom = htmlspecialchars($userProfile['nom'] ?? 'Nom', ENT_QUOTES, 'UTF-8');
    $prenom = htmlspecialchars($userProfile['prenom'] ?? 'Prénom', ENT_QUOTES, 'UTF-8');
    return $prenom . ' ' . $nom;
}

// Récupération du nom complet
$fullName = getFullName($userProfile);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mon Compte - Le Gourmet Nomade</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background-color:  #333438;
            color: white;
        }
        .navbar {
            background-color:  #333438;
        }
        .btn-reservation {
            background-color: #c1a068;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 30px;
            font-weight: 500;
            transition: all 0.3s;
        }
        .btn-reservation:hover {
            background-color: #a88c5a;
            color: white;
        }
        .btn-danger {
            border-radius: 30px;
        }
        .dashboard-section {
            min-height: 80vh;
            padding: 120px 0 40px;
        }
        .profile-card, .reservation-card, .calendar-card {
            background-color: #1e1e1e;
            border-radius: 15px;
            border: 1px solid #333;
        }
        .nav-pills .nav-link {
            color: white;
            transition: all 0.3s;
        }
        .nav-pills .nav-link:hover {
            color: #c1a068;
        }
        .nav-pills .nav-link.active {
            background-color: transparent;
            color: #c1a068;
            font-weight: bold;
        }
        .form-control, .form-select {
            background-color: #2c2c2c;
            border: 1px solid #444;
            color: white;
        }
        .form-control:focus, .form-select:focus {
            background-color: #2c2c2c;
            color: white;
            border-color: #c1a068;
            box-shadow: 0 0 0 0.25rem rgba(193, 160, 104, 0.25);
        }
        .custom-card-header {
            background-color: rgba(193, 160, 104, 0.2);
            color: #c1a068;
            border-bottom: 1px solid #444;
        }
        .table {
            color: white;
        }
        .accent-color {
            color: #c1a068;
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg fixed-top">
            <div class="container">
                <a class="navbar-brand" href="index.php">
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
                        <li class="nav-item"><a class="nav-link" href="reservation.php">Réserver</a></li>
                    </ul>
                    <div class="ms-3 dropdown">
                        <button class="btn btn-reservation dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user me-1"></i> <?php echo $fullName; ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="dashboard.php">Mon compte</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Se déconnecter</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <main class="dashboard-section">
        <div class="container">
            <h1 class="text-center mt-5">
                <span class="accent-color">Bienvenue, <?php echo $fullName; ?> !</span>
            </h1>
            
            <div class="row mt-5">
                <div class="col-md-3 mb-4">
                    <div class="profile-card bg-dark bg-opacity-50 p-3">
                        <div class="text-center mb-4">
                            <i class="fas fa-user-circle fa-5x accent-color"></i>
                            <h4 class="mt-3"><?php echo $fullName; ?></h4>
                            
                        </div>
                        
                        <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                            <a href="#v-pills-profile" class="nav-link mb-2 active" id="v-pills-profile-tab" data-bs-toggle="pill" data-bs-target="#v-pills-profile" role="tab">
                                <i class="fas fa-user-cog me-2"></i> Profil
                            </a>
                            <a href="user_reservation.php" class="nav-link mb-2">
                                <i class="fas fa-calendar-alt me-2"></i> Mes réservations
                            </a>
                            <a href="reservation.php" class="nav-link mb-2">
                                <i class="fas fa-plus-circle me-2"></i> Nouvelle réservation
                            </a>
                        </div>
                        
                        <div class="mt-4 text-center">
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                             <i class="fas fa-user-slash me-1"></i> Supprimer le compte
                        </button>
                        </div>

                        <!-- Modal de confirmation de suppression -->
                    <div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
                         <div class="modal-dialog">
                                <div class="modal-content bg-dark text-white">
                                    <div class="modal-header border-bottom border-secondary">
                                        <h5 class="modal-title" id="deleteAccountModalLabel">Confirmation de suppression</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                <div class="modal-body">
                                    <p class="text-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Attention :</strong> Vous êtes sur le point de supprimer définitivement votre compte.
                                    </p>
                                    <p>Cette action est <strong>irréversible</strong> et entraînera la suppression de toutes vos réservations et informations personnelles.</p>
                                    <p>Êtes-vous sûr de vouloir continuer ?</p>
                                </div>
                            <div class="modal-footer border-top border-secondary">
                                 <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                <form method="POST" action="dashboard.php">
                                 <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <button type="submit" name="delete_account" class="btn btn-danger">
                                <i class="fas fa-user-slash me-1"></i> Supprimer définitivement
                                </button>
                                </form>
                            </div>
        </div>
    </div>
</div>
                    </div>
                </div>
                
                <div class="col-md-9">
                    <div class=" profile-card bg-dark bg-opacity-50 p-3" id="v-pills-tabContent">
                        <!-- Onglet Profil -->
<div class="tab-pane fade show active" id="v-pills-profile" role="tabpanel">
    <div class="p-3">
        <h4 class="mb-4 accent-color"><i class="fas fa-user-cog me-2"></i> Informations personnelles</h4>
        <?php echo $profileUpdateMessage; ?>
        <form method="POST" action="dashboard.php">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="row mb-3">
                <div class="col-md-6 mb-3">
                    <label for="nom" class="form-label text-white">Nom</label>
                    <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($userProfile['nom'], ENT_QUOTES, 'UTF-8'); ?>" required style="background-color: #000; border: 1px solid #c1a068; color: white;">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="prenom" class="form-label text-white">Prénom</label>
                    <input type="text" class="form-control" id="prenom" name="prenom" value="<?php echo htmlspecialchars($userProfile['prenom'], ENT_QUOTES, 'UTF-8'); ?>" required style="background-color: #000; border: 1px solid #c1a068; color: white;">
                </div>
            </div>
            
            <div class="mb-3">
                <label for="email" class="form-label text-white">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($userProfile['email'], ENT_QUOTES, 'UTF-8'); ?>" required style="background-color: #000; border: 1px solid #c1a068; color: white;">
            </div>
            
            <div class="mb-3">
                <label for="telephone" class="form-label text-white">Téléphone</label>
                <input type="tel" class="form-control" id="telephone" name="telephone" value="<?php echo htmlspecialchars($userProfile['telephone'], ENT_QUOTES, 'UTF-8'); ?>" style="background-color: #000; border: 1px solid #c1a068; color: white;">
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6 mb-3">
                    <label for="date_naissance" class="form-label text-white">Date de naissance</label>
                    <input type="date" class="form-control" id="date_naissance" name="date_naissance" value="<?php echo htmlspecialchars($userProfile['date_naissance'], ENT_QUOTES, 'UTF-8'); ?>" style="background-color: #000; border: 1px solid #c1a068; color: white;">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="code_postal" class="form-label text-white">Code postal</label>
                    <input type="text" class="form-control" id="code_postal" name="code_postal" value="<?php echo htmlspecialchars($userProfile['code_postal'], ENT_QUOTES, 'UTF-8'); ?>" style="background-color: #000; border: 1px solid #c1a068; color: white;">
                </div>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="submit" name="update_profile" class="btn btn-reservation">
                    <i class="fas fa-save me-1"></i> Enregistrer les modifications
                </button>
            </div>
        </form>
    </div>
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