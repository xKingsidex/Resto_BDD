<?php
session_start();
require_once "config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$sql = "SELECT * FROM reservations WHERE user_id = :user_id ORDER BY date_reservation, heure_reservation";
$stmt = $pdo->prepare($sql);
$stmt->execute([":user_id" => $user_id]);
$reservations = $stmt->fetchAll();

// Générer un token CSRF pour la protection des formulaires
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Récupérer les informations utilisateur si disponibles
$user_info = [];
try {
    // Récupérer la structure de la table users pour déterminer quelles colonnes existent
    $sql = "SHOW COLUMNS FROM users";
    $stmt = $pdo->query($sql);
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Construire une requête qui ne demande que les colonnes existantes
    $select_fields = [];
    $display_fields = [];
    
    // Chercher des colonnes utiles pour l'affichage dans l'ordre de préférence
    $possible_name_fields = ['username', 'nom', 'name', 'user_name', 'login', 'email', 'prenom'];
    
    foreach ($possible_name_fields as $field) {
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
    $stmt->execute([":user_id" => $user_id]);
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user_info) {
        $user_info = [];
    }
} catch (Exception $e) {
    $message = "<div class='alert alert-danger'>Erreur lors de la récupération des données utilisateur: " . $e->getMessage() . "</div>";
}

// Gestion de la suppression de réservation si nécessaire
$message = "";
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    try {
        $reservation_id = (int)$_GET['id'];
        
        // Vérifier que la réservation appartient à l'utilisateur
        $check_sql = "SELECT * FROM reservations WHERE id = :id AND user_id = :user_id";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([":id" => $reservation_id, ":user_id" => $user_id]);
        
        if ($check_stmt->rowCount() > 0) {
            // Supprimer la réservation
            $delete_sql = "DELETE FROM reservations WHERE id = :id";
            $delete_stmt = $pdo->prepare($delete_sql);
            $delete_stmt->execute([":id" => $reservation_id]);
            
            $message = "<div class='alert alert-success'>La réservation a été annulée avec succès.</div>";
            
            // Rafraîchir la liste des réservations
            $sql = "SELECT * FROM reservations WHERE user_id = :user_id ORDER BY date_reservation, heure_reservation";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([":user_id" => $user_id]);
            $reservations = $stmt->fetchAll();
        } else {
            $message = "<div class='alert alert-danger'>Vous n'êtes pas autorisé à annuler cette réservation.</div>";
        }
    } catch (Exception $e) {
        $message = "<div class='alert alert-danger'>Erreur lors de l'annulation de la réservation: " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mes Réservations - Le Gourmet Nomade</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..700&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .reservation-container {
            background-color: rgba(0, 0, 0, 0.5);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .reservation-table th {
            color: #c1a068;
            background-color: #1e1e1e;
            border-color: #333;
        }
        
        .reservation-table td {
            border-color: #333;
            vertical-align: middle;
        }
        
        .btn-cancel {
            background-color: #8B0000;
            color: white;
            border: none;
            transition: all 0.3s;
        }
        
        .btn-cancel:hover {
            background-color: #5c0000;
            color: white;
        }
        
        .btn-details {
            background-color: #c1a068;
            color: #1e1e1e;
            border: none;
            transition: all 0.3s;
        }
        
        .btn-details:hover {
            background-color: #a38457;
            color: #1e1e1e;
        }
        
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 5px;
        }
        
        .status-upcoming {
            background-color: #28a745;
        }
        
        .status-past {
            background-color: #6c757d;
        }
        
        .reservation-card {
            background-color: #1e1e1e;
            border: 1px solid #333;
            border-radius: 8px;
            margin-bottom: 15px;
            padding: 15px;
            transition: all 0.3s;
        }
        
        .reservation-card:hover {
            border-color: #c1a068;
            box-shadow: 0 0 10px rgba(193, 160, 104, 0.3);
        }
        
        .reservation-header {
            color: #c1a068;
            font-weight: bold;
            border-bottom: 1px solid #333;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .reservation-info {
            color: #fff;
            margin-bottom: 5px;
        }
        
        .reservation-actions {
            margin-top: 15px;
            display: flex;
            justify-content: flex-end;
        }
        
        .empty-state {
            text-align: center;
            padding: 30px;
        }
        
        .empty-state i {
            font-size: 48px;
            color: #c1a068;
            margin-bottom: 15px;
        }
        
        .reservation-badge {
            background-color: #c1a068;
            color: #1e1e1e;
            font-weight: bold;
            padding: 5px 10px;
            border-radius: 50px;
            display: inline-block;
            margin-left: 10px;
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
                    <a href="dashboard.php" class="btn btn-reservation ms-3">Mon Compte</a>
                </div>
            </div>
        </nav>
    </header>

    <main class="pt-5">
        <section id="reservations" class="min-vh-100 d-flex justify-content-center align-items-center flex-column text-center py-5">
            <p class="fs-1 mt-5" style="color: #c1a068;">MES RÉSERVATIONS</p>
            <div class="container flex-column align-items-center">
                <div class="row justify-content-center">
                    <div class="col-md-10">
                        <?php echo $message; ?>
                        
                        <div class="p-4 rounded-3 bg-dark bg-opacity-50 reservation-container">
                            <?php if (count($reservations) > 0): ?>
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h3 class="fs-4" style="color: #c1a068;">Historique de vos réservations</h3>
                                    <a href="reservation.php" class="btn btn-reservation">
                                        <i class="fas fa-plus-circle me-2"></i> Nouvelle réservation
                                    </a>
                                </div>
                                
                                <!-- Mobile view (cards) -->
                                <div class="d-block d-md-none">
                                    <?php 
                                    $today = date('Y-m-d');
                                    $upcomingCount = 0;
                                    $pastCount = 0;
                                    
                                    foreach ($reservations as $res): 
                                        $isPast = ($res["date_reservation"] < $today) || 
                                                ($res["date_reservation"] == $today && $res["heure_reservation"] < date('H:i'));
                                        
                                        if ($isPast) {
                                            $pastCount++;
                                        } else {
                                            $upcomingCount++;
                                        }
                                    endforeach;
                                    ?>
                                    
                                    <div class="reservation-tabs mb-4">
                                        <button class="btn btn-reservation me-2 active" data-tab="upcoming">
                                            À venir <span class="reservation-badge"><?php echo $upcomingCount; ?></span>
                                        </button>
                                        <button class="btn btn-outline-secondary" data-tab="past">
                                            Passées <span class="reservation-badge"><?php echo $pastCount; ?></span>
                                        </button>
                                    </div>
                                    
                                    <div id="upcoming-reservations">
                                        <?php 
                                        $hasUpcoming = false;
                                        foreach ($reservations as $res): 
                                            $isPast = ($res["date_reservation"] < $today) || 
                                                    ($res["date_reservation"] == $today && $res["heure_reservation"] < date('H:i'));
                                            
                                            if (!$isPast):
                                                $hasUpcoming = true;
                                                $date = new DateTime($res["date_reservation"]);
                                        ?>
                                        <div class="reservation-card">
                                            <div class="reservation-header">
                                                <span class="status-indicator status-upcoming"></span>
                                                <?php echo $date->format('d/m/Y'); ?> à <?php echo $res["heure_reservation"]; ?>
                                            </div>
                                            <div class="reservation-info">
                                                <i class="fas fa-users me-2"></i> <?php echo $res["nb_personnes"]; ?> personne(s)
                                            </div>
                                            <?php if (!empty($res["description"])): ?>
                                            <div class="reservation-info">
                                                <i class="fas fa-comment-alt me-2"></i> <?php echo $res["description"]; ?>
                                            </div>
                                            <?php endif; ?>
                                            <div class="reservation-actions">
                                                <a href="user_reservation.php?action=delete&id=<?php echo $res['id']; ?>" class="btn btn-cancel btn-sm" 
                                                   onclick="return confirm('Êtes-vous sûr de vouloir annuler cette réservation ?');">
                                                    <i class="fas fa-times me-1"></i> Annuler
                                                </a>
                                            </div>
                                        </div>
                                        <?php 
                                            endif;
                                        endforeach; 
                                        
                                        if (!$hasUpcoming):
                                        ?>
                                        <div class="empty-state">
                                            <i class="far fa-calendar-alt"></i>
                                            <p>Vous n'avez aucune réservation à venir</p>
                                            <a href="reservation.php" class="btn btn-reservation mt-3">Réserver une table</a>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div id="past-reservations" style="display: none;">
                                        <?php 
                                        $hasPast = false;
                                        foreach ($reservations as $res): 
                                            $isPast = ($res["date_reservation"] < $today) || 
                                                    ($res["date_reservation"] == $today && $res["heure_reservation"] < date('H:i'));
                                            
                                            if ($isPast):
                                                $hasPast = true;
                                                $date = new DateTime($res["date_reservation"]);
                                        ?>
                                        <div class="reservation-card">
                                            <div class="reservation-header">
                                                <span class="status-indicator status-past"></span>
                                                <?php echo $date->format('d/m/Y'); ?> à <?php echo $res["heure_reservation"]; ?>
                                            </div>
                                            <div class="reservation-info">
                                                <i class="fas fa-users me-2"></i> <?php echo $res["nb_personnes"]; ?> personne(s)
                                            </div>
                                            <?php if (!empty($res["description"])): ?>
                                            <div class="reservation-info">
                                                <i class="fas fa-comment-alt me-2"></i> <?php echo $res["description"]; ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php 
                                            endif;
                                        endforeach; 
                                        
                                        if (!$hasPast):
                                        ?>
                                        <div class="empty-state">
                                            <i class="far fa-calendar-check"></i>
                                            <p>Vous n'avez aucune réservation passée</p>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Desktop view (table) -->
                                <div class="d-none d-md-block">
                                    <table class="table table-dark table-hover reservation-table">
                                        <thead>
                                            <tr>
                                                <th>Statut</th>
                                                <th>Date</th>
                                                <th>Heure</th>
                                                <th>Personnes</th>
                                                <th>Commentaires</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $today = date('Y-m-d');
                                            foreach ($reservations as $res): 
                                                $date = new DateTime($res["date_reservation"]);
                                                $isPast = ($res["date_reservation"] < $today) || 
                                                        ($res["date_reservation"] == $today && $res["heure_reservation"] < date('H:i'));
                                            ?>
                                            <tr>
                                                <td>
                                                    <?php if ($isPast): ?>
                                                        <span class="status-indicator status-past"></span> Passée
                                                    <?php else: ?>
                                                        <span class="status-indicator status-upcoming"></span> À venir
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $date->format('d/m/Y'); ?></td>
                                                <td><?php echo $res["heure_reservation"]; ?></td>
                                                <td><?php echo $res["nb_personnes"]; ?></td>
                                                <td>
                                                    <?php if (!empty($res["description"])): ?>
                                                        <?php 
                                                        $desc = $res["description"];
                                                        echo (strlen($desc) > 30) ? substr($desc, 0, 27) . '...' : $desc; 
                                                        ?>
                                                    <?php else: ?>
                                                        <em class="text-muted">Aucun</em>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!$isPast): ?>
                                                    <a href="user_reservation.php?action=delete&id=<?php echo $res['id']; ?>" class="btn btn-cancel btn-sm"
                                                       onclick="return confirm('Êtes-vous sûr de vouloir annuler cette réservation ?');">
                                                        <i class="fas fa-times me-1"></i> Annuler
                                                    </a>
                                                    <?php else: ?>
                                                    <span class="text-muted">Terminée</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="empty-state py-5">
                                    <i class="far fa-calendar-alt mb-3"></i>
                                    <h3 class="mb-3">Vous n'avez aucune réservation</h3>
                                    <p class="mb-4">Réservez une table pour profiter de notre cuisine gastronomique</p>
                                    <a href="reservation.php" class="btn btn-reservation btn-lg">
                                        <i class="fas fa-utensils me-2"></i> Réserver une table
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="text-center mt-4">
                            <a href="dashboard.php" class="btn btn-outline-light">
                                <i class="fas fa-arrow-left me-2"></i> Retour au tableau de bord
                            </a>
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
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Gestion des onglets pour mobile
        const upcomingReservations = document.getElementById('upcoming-reservations');
        const pastReservations = document.getElementById('past-reservations');
        const tabButtons = document.querySelectorAll('[data-tab]');
        
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                const tab = this.getAttribute('data-tab');
                
                // Mettre à jour les classes actives
                tabButtons.forEach(btn => btn.classList.remove('active', 'btn-reservation'));
                tabButtons.forEach(btn => btn.classList.add('btn-outline-secondary'));
                this.classList.remove('btn-outline-secondary');
                this.classList.add('active', 'btn-reservation');
                
                // Afficher le contenu approprié
                if (tab === 'upcoming') {
                    upcomingReservations.style.display = 'block';
                    pastReservations.style.display = 'none';
                } else {
                    upcomingReservations.style.display = 'none';
                    pastReservations.style.display = 'block';
                }
            });
        });
    });
    </script>
</body>
</html>