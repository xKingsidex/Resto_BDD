<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// Fonction pour récupérer les rendez-vous de l'utilisateur (à implémenter avec votre base de données)
function getUserAppointments($userId) {
    // Cette fonction doit être implémentée pour récupérer les rendez-vous depuis la base de données
    // Exemple de structure de retour:
    return [
        [
            'id' => 1,
            'date' => '2025-03-10',
            'heure' => '19:30',
            'personnes' => 2,
            'commentaire' => 'Anniversaire'
        ],
        [
            'id' => 2,
            'date' => '2025-03-15',
            'heure' => '12:30',
            'personnes' => 4,
            'commentaire' => 'Déjeuner d\'affaires'
        ]
    ];
}

// Récupération des rendez-vous de l'utilisateur
$appointments = getUserAppointments($_SESSION["user_id"]);

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
    <!-- FullCalendar -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.0/main.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.0/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.0/locales/fr.js"></script>
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
        .fc-theme-standard th {
            border-color: #444;
        }
        .fc .fc-daygrid-day.fc-day-today {
            background-color: rgba(193, 160, 104, 0.15) !important;
        }
        .fc-theme-standard td, .fc-theme-standard th {
            border-color: #444;
        }
        .fc .fc-button-primary {
            background-color: #c1a068;
            border-color: #c1a068;
        }
        .fc .fc-button-primary:hover {
            background-color: #a88c5a;
            border-color: #a88c5a;
        }
        .fc .fc-button-primary:disabled {
            background-color: #7d6844;
            border-color: #7d6844;
        }
        .fc-event {
            background-color: #c1a068;
            border-color: #c1a068;
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
                            <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($_SESSION["nom"]); ?>
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
                <span class="accent-color">Bienvenue, <?php echo htmlspecialchars($_SESSION["nom"]); ?> !</span>
            </h1>
            
            <div class="row">
                <div class="col-md-3 mb-4">
                    <div class="profile-card bg-dark bg-opacity-50 p-3">
                        <div class="text-center mb-4">
                            <i class="fas fa-user-circle fa-5x accent-color"></i>
                            <h4 class="mt-3"><?php echo htmlspecialchars($_SESSION["nom"] . " " . ($_SESSION["prenom"] ?? "")); ?></h4>
                            <p class="text-muted"><?php echo htmlspecialchars($_SESSION["email"] ?? ""); ?></p>
                        </div>
                        
                        <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                            <button class="nav-link mb-2" id="v-pills-reservations-tab" data-bs-toggle="pill" data-bs-target="#v-pills-reservations" type="button" role="tab">
                                <i class="fas fa-calendar-alt me-2"></i> Mes réservations
                            </button>
                            <button class="nav-link mb-2" id="v-pills-new-tab" data-bs-toggle="pill" data-bs-target="#v-pills-new" type="button" role="tab">
                                <i class="fas fa-plus-circle me-2"></i> Nouvelle réservation
                            </button>
                            <button class="nav-link mb-2" id="v-pills-profile-tab" data-bs-toggle="pill" data-bs-target="#v-pills-profile" type="button" role="tab">
                                <i class="fas fa-user-cog me-2"></i> Profil
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0