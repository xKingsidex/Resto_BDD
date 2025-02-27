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

// Configuration de la connexion à la base de données
$servername = "localhost"; // À modifier selon votre configuration
$username = "root"; // À modifier selon votre configuration
$password = ""; // À modifier selon votre configuration
$dbname = "nom_de_votre_base"; // À modifier selon votre configuration

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connexion échouée: " . $e->getMessage());
}

// Fonction pour récupérer les rendez-vous de l'utilisateur depuis la base de données
function getUserAppointments($conn, $userId) {
    $stmt = $conn->prepare("SELECT id, date, heure, personnes, commentaire FROM reservations WHERE user_id = :user_id ORDER BY date, heure");
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Récupération des rendez-vous de l'utilisateur
$appointments = getUserAppointments($conn, $_SESSION["user_id"]);

// Récupération des informations de l'utilisateur depuis la base de données
$stmt = $conn->prepare("SELECT email, nom, prenom, date_naissance, code_postal, telephone FROM users WHERE id = :id");
$stmt->bindParam(':id', $_SESSION["user_id"]);
$stmt->execute();
$userProfile = $stmt->fetch(PDO::FETCH_ASSOC);

// Traitement du formulaire de modification du profil
$profileUpdateMessage = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    // Vérification du token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $profileUpdateMessage = '<div class="alert alert-danger">Erreur de sécurité. Veuillez réessayer.</div>';
    } else {
        // Validation et nettoyage des données
        $nom = htmlspecialchars(trim($_POST['nom']));
        $prenom = htmlspecialchars(trim($_POST['prenom']));
        $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL) ? trim($_POST['email']) : '';
        $telephone = htmlspecialchars(trim($_POST['telephone']));
        $date_naissance = htmlspecialchars(trim($_POST['date_naissance']));
        $code_postal = htmlspecialchars(trim($_POST['code_postal']));
        
        if (empty($email)) {
            $profileUpdateMessage = '<div class="alert alert-danger">Adresse email invalide.</div>';
        } else {
            try {
                // Mise à jour dans la base de données
                $updateStmt = $conn->prepare("UPDATE users SET nom = :nom, prenom = :prenom, email = :email, telephone = :telephone, date_naissance = :date_naissance, code_postal = :code_postal WHERE id = :id");
                $updateStmt->bindParam(':nom', $nom);
                $updateStmt->bindParam(':prenom', $prenom);
                $updateStmt->bindParam(':email', $email);
                $updateStmt->bindParam(':telephone', $telephone);
                $updateStmt->bindParam(':date_naissance', $date_naissance);
                $updateStmt->bindParam(':code_postal', $code_postal);
                $updateStmt->bindParam(':id', $_SESSION["user_id"]);
                $updateStmt->execute();
                
                // Mise à jour des informations de session
                $_SESSION["nom"] = $nom;
                $_SESSION["prenom"] = $prenom;
                $_SESSION["email"] = $email;
                
                // Rafraîchir les données du profil
                $stmt = $conn->prepare("SELECT email, nom, prenom, date_naissance, code_postal, telephone FROM users WHERE id = :id");
                $stmt->bindParam(':id', $_SESSION["user_id"]);
                $stmt->execute();
                $userProfile = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $profileUpdateMessage = '<div class="alert alert-success">Votre profil a été mis à jour avec succès.</div>';
            } catch(PDOException $e) {
                $profileUpdateMessage = '<div class="alert alert-danger">Erreur lors de la mise à jour: ' . $e->getMessage() . '</div>';
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
        .nav-pills .nav-link.active {
            background-color: #c1a068;
        }
        .nav-pills .nav-link {
            color: white;
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
                            <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($userProfile['nom']); ?>
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
                <span class="accent-color">Bienvenue, <?php echo htmlspecialchars($userProfile['nom']); ?> !</span>
            </h1>
            
            <div class="row">
                <div class="col-md-3 mb-4">
                    <div class="profile-card bg-dark bg-opacity-50 p-3">
                        <div class="text-center mb-4">
                            <i class="fas fa-user-circle fa-5x accent-color"></i>
                            <h4 class="mt-3"><?php echo htmlspecialchars($userProfile['nom'] . " " . $userProfile['prenom']); ?></h4>
                            <p class="text-muted"><?php echo htmlspecialchars($userProfile['email']); ?></p>
                        </div>
                        
                        <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                            <button class="nav-link active mb-2" id="v-pills-profile-tab" data-bs-toggle="pill" data-bs-target="#v-pills-profile" type="button" role="tab">
                                <i class="fas fa-user-cog me-2"></i> Profil
                            </button>
                            <button class="nav-link mb-2" id="v-pills-reservations-tab" data-bs-toggle="pill" data-bs-target="#v-pills-reservations" type="button" role="tab">
                                <i class="fas fa-calendar-alt me-2"></i> Mes réservations
                            </button>
                            <button class="nav-link mb-2" id="v-pills-new-tab" data-bs-toggle="pill" data-bs-target="#v-pills-new" type="button" role="tab">
                                <i class="fas fa-plus-circle me-2"></i> Nouvelle réservation
                            </button>
                        </div>
                        
                        <div class="mt-4 text-center">
                            <a href="logout.php" class="btn btn-danger">
                                <i class="fas fa-sign-out-alt me-1"></i> Se déconnecter
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-9">
                    <div class="tab-content" id="v-pills-tabContent">
                        <!-- Onglet Profil -->
                        <div class="tab-pane fade show active" id="v-pills-profile" role="tabpanel">
                            <div class="card profile-card">
                                <div class="card-header custom-card-header">
                                    <h4 class="mb-0"><i class="fas fa-user-cog me-2"></i> Informations personnelles</h4>
                                </div>
                                <div class="card-body">
                                    <?php echo $profileUpdateMessage; ?>
                                    <form method="POST" action="dashboard.php">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        
                                        <div class="row mb-3">
                                            <div class="col-md-6 mb-3">
                                                <label for="nom" class="form-label">Nom</label>
                                                <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($userProfile['nom']); ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="prenom" class="form-label">Prénom</label>
                                                <input type="text" class="form-control" id="prenom" name="prenom" value="<?php echo htmlspecialchars($userProfile['prenom']); ?>" required>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($userProfile['email']); ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="telephone" class="form-label">Téléphone</label>
                                            <input type="tel" class="form-control" id="telephone" name="telephone" value="<?php echo htmlspecialchars($userProfile['telephone']); ?>">
                                        </div>
                                        
                                        <div class="row mb-3">
                                            <div class="col-md-6 mb-3">
                                                <label for="date_naissance" class="form-label">Date de naissance</label>
                                                <input type="date" class="form-control" id="date_naissance" name="date_naissance" value="<?php echo htmlspecialchars($userProfile['date_naissance']); ?>">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="code_postal" class="form-label">Code postal</label>
                                                <input type="text" class="form-control" id="code_postal" name="code_postal" value="<?php echo htmlspecialchars($userProfile['code_postal']); ?>">
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
                        
                        <!-- Onglet Réservations -->
                        <div class="tab-pane fade" id="v-pills-reservations" role="tabpanel">
                            <div class="card reservation-card">
                                <div class="card-header custom-card-header">
                                    <h4 class="mb-0"><i class="fas fa-calendar-alt me-2"></i> Mes réservations</h4>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($appointments)): ?>
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i> Vous n'avez pas encore de réservation.
                                        </div>
                                        <div class="text-center mt-3">
                                            <button class="btn btn-reservation" data-bs-toggle="pill" data-bs-target="#v-pills-new">
                                                <i class="fas fa-plus-circle me-1"></i> Faire une réservation
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Heure</th>
                                                        <th>Personnes</th>
                                                        <th>Commentaire</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($appointments as $appointment): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($appointment['date']); ?></td>
                                                            <td><?php echo htmlspecialchars($appointment['heure']); ?></td>
                                                            <td><?php echo htmlspecialchars($appointment['personnes']); ?></td>
                                                            <td><?php echo htmlspecialchars($appointment['commentaire']); ?></td>
                                                            <td>
                                                                <form method="POST" action="cancel_reservation.php" style="display: inline;">
                                                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                                    <input type="hidden" name="reservation_id" value="<?php echo $appointment['id']; ?>">
                                                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir annuler cette réservation ?');">
                                                                        <i class="fas fa-times"></i> Annuler
                                                                    </button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Onglet Nouvelle Réservation -->
                        <div class="tab-pane fade" id="v-pills-new" role="tabpanel">
                            <div class="card profile-card">
                                <div class="card-header custom-card-header">
                                    <h4 class="mb-0"><i class="fas fa-plus-circle me-2"></i> Nouvelle réservation</h4>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="process_reservation.php">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        
                                        <div class="row mb-3">
                                            <div class="col-md-6 mb-3">
                                                <label for="date" class="form-label">Date</label>
                                                <input type="date" class="form-control" id="date" name="date" min="<?php echo date('Y-m-d'); ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="heure" class="form-label">Heure</label>
                                                <select class="form-select" id="heure" name="heure" required>
                                                    <option value="">Choisir une heure</option>
                                                    <option value="12:00">12:00</option>
                                                    <option value="12:30">12:30</option>
                                                    <option value="13:00">13:00</option>
                                                    <option value="13:30">13:30</option>
                                                    <option value="14:00">14:00</option>
                                                    <option value="19:00">19:00</option>
                                                    <option value="19:30">19:30</option>
                                                    <option value="20:00">20:00</option>
                                                    <option value="20:30">20:30</option>
                                                    <option value="21:00">21:00</option>
                                                    <option value="21:30">21:30</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="personnes" class="form-label">Nombre de personnes</label>
                                            <select class="form-select" id="personnes" name="personnes" required>
                                                <option value="">Choisir le nombre de personnes</option>
                                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                                    <option value="<?php echo $i; ?>"><?php echo $i; ?> personne<?php echo $i > 1 ? 's' : ''; ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="commentaire" class="form-label">Commentaire (allergies, occasion spéciale...)</label>
                                            <textarea class="form-control" id="commentaire" name="commentaire" rows="3"></textarea>
                                        </div>
                                        
                                        <div class="d-grid gap-2">
                                            <button type="submit" class="btn btn-reservation">
                                                <i class="fas fa-check-circle me-1"></i> Confirmer la réservation
                                            </button>
                                        </div>
                                    </form>
                                </div>
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