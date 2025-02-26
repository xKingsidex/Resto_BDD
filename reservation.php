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

// Générer un token CSRF pour la protection des formulaires
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = "";
$user_logged_in = isset($_SESSION["user_id"]);

// Informations utilisateur par défaut
$user_info = [];

// Heures disponibles pour les réservations
$heures_disponibles = [
    '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', 
    '19:00', '19:30', '20:00', '20:30', '21:00', '21:30'
];

// Récupérer les réservations existantes
$reservations_existantes = [];
if ($db_connected) {
    try {
        $sql = "SELECT date_reservation, heure_reservation FROM reservations";
        $stmt = $pdo->query($sql);
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $reservations_existantes[] = $row['date_reservation'] . ' ' . $row['heure_reservation'];
        }
    } catch (Exception $e) {
        $message = "<div class='alert alert-danger'>Erreur lors de la récupération des réservations: " . $e->getMessage() . "</div>";
    }
}

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
        $possible_name_fields = ['username', 'nom', 'name', 'user_name', 'login', 'email', 'prenom'];
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

// Vérifier la disponibilité d'un créneau (format AJAX)
if (isset($_GET['check_availability']) && $db_connected) {
    try {
        $date = $_GET['date'] ?? '';
        $heure = $_GET['heure'] ?? '';
        
        if (empty($date) || empty($heure)) {
            echo json_encode(['available' => false, 'message' => 'Date ou heure manquante']);
            exit;
        }
        
        // Vérifier si le créneau est disponible
        $sql = "SELECT COUNT(*) FROM reservations WHERE date_reservation = :date AND heure_reservation = :heure";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([":date" => $date, ":heure" => $heure]);
        $count = $stmt->fetchColumn();
        
        echo json_encode(['available' => ($count == 0)]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['available' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// Obtenir les créneaux disponibles pour une date donnée (format AJAX)
if (isset($_GET['get_time_slots']) && $db_connected) {
    try {
        $date = $_GET['date'] ?? '';
        
        if (empty($date)) {
            echo json_encode(['error' => 'Date manquante']);
            exit;
        }
        
        // Récupérer les réservations pour cette date
        $sql = "SELECT heure_reservation FROM reservations WHERE date_reservation = :date";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([":date" => $date]);
        $reserved_slots = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Filtrer les heures disponibles
        $available_slots = array_filter($heures_disponibles, function($heure) use ($reserved_slots) {
            return !in_array($heure, $reserved_slots);
        });
        
        echo json_encode(['slots' => array_values($available_slots)]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $user_logged_in && $db_connected) {
    // Vérifier le token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = "<div class='alert alert-danger'>Erreur de validation du formulaire. Veuillez réessayer.</div>";
    } else {
        try {
            $date = $_POST["date"] ?? '';
            $heure = $_POST["heure"] ?? '';
            $description = htmlspecialchars($_POST["description"] ?? '');
            $personnes = (int)($_POST["personnes"] ?? 1);
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
                    $sql = "INSERT INTO reservations (user_id, date_reservation, heure_reservation, description, nb_personnes) 
                            VALUES (:user_id, :date, :heure, :description, :personnes)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ":user_id" => $user_id,
                        ":date" => $date,
                        ":heure" => $heure,
                        ":description" => $description,
                        ":personnes" => $personnes
                    ]);
                    $message = "<div class='alert alert-success'>✅ Réservation enregistrée avec succès !</div>";
                }
            }
        } catch (Exception $e) {
            $message = "<div class='alert alert-danger'>Erreur lors de la réservation: " . $e->getMessage() . "</div>";
        }
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.css">
    <style>
        .calendar-container {
            background-color: rgba(0, 0, 0, 0.5);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .time-slot {
            display: inline-block;
            margin: 5px;
            padding: 10px 15px;
            background-color: #1e1e1e;
            color: #c1a068;
            border: 1px solid #c1a068;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .time-slot:hover {
            background-color: #c1a068;
            color: #1e1e1e;
        }
        
        .time-slot.selected {
            background-color: #c1a068;
            color: #1e1e1e;
            border: 1px solid #c1a068;
        }
        
        .time-slot.disabled {
            background-color: #444;
            color: #888;
            border: 1px solid #555;
            cursor: not-allowed;
        }
        
        .flatpickr-calendar {
            background: #1e1e1e;
            border: 1px solid #c1a068;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.5);
        }
        
        .flatpickr-day {
            color: #c1a068;
        }
        
        .flatpickr-day.selected {
            background: #c1a068;
            color: #1e1e1e;
            border-color: #c1a068;
        }
        
        .flatpickr-day:hover {
            background: rgba(193, 160, 104, 0.3);
        }
        
        .flatpickr-day.flatpickr-disabled {
            color: #555;
        }
        
        .flatpickr-months .flatpickr-month,
        .flatpickr-current-month .flatpickr-monthDropdown-months,
        .flatpickr-weekday {
            color: #c1a068;
            background: #1e1e1e;
        }
        
        .flatpickr-months .flatpickr-prev-month, 
        .flatpickr-months .flatpickr-next-month {
            color: #c1a068;
            fill: #c1a068;
        }
        
        .custom-input {
            background-color: rgba(0, 0, 0, 0.5);
            border: 1px solid #c1a068;
            color: #fff;
        }
        
        .input-group-text {
            background-color: #c1a068;
            color: #1e1e1e;
            border: 1px solid #c1a068;
        }
        
        .reservation-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        
        .reservation-step {
            flex: 1;
            text-align: center;
            padding: 15px;
            position: relative;
        }
        
        .reservation-step:not(:last-child):after {
            content: "";
            position: absolute;
            top: 50%;
            right: 0;
            width: 100%;
            height: 2px;
           
            transform: translateY(-50%);
            z-index: -1;
        }
        
        .step-number {
            display: inline-block;
            width: 40px;
            height: 40px;
            line-height: 40px;
            border-radius: 50%;
            background-color: #1e1e1e;
            color: #c1a068;
            border: 2px solid #c1a068;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }
        
        .step-active .step-number {
            background-color: #c1a068;
            color: #1e1e1e;
        }
        
        .step-title {
            color: #c1a068;
            font-weight: bold;
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
            <p class="fs-1 mt-5" style="color: #c1a068;">RESERVATION</p>
            <div class="container flex-column align-items-center">
                <div class="row justify-content-center">
                    <div class="col-md-10">
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
                            <div class="reservation-steps">
                                <div class="reservation-step step-active" id="step1">
                                    <div class="step-number">1</div>
                                    <div class="step-title">Date</div>
                                </div>
                                <div class="reservation-step" id="step2">
                                    <div class="step-number">2</div>
                                    <div class="step-title">Heure</div>
                                </div>
                                <div class="reservation-step" id="step3">
                                    <div class="step-number">3</div>
                                    <div class="step-title">Détails</div>
                                </div>
                            </div>
                            
                            <div class="p-4 rounded-3 bg-dark bg-opacity-50">
                                <form method="POST" action="reservation.php" id="reservationForm">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    
                                    <div id="step1-content">
                                        <h3 class="mb-4" style="color: #c1a068;">Choisissez une date</h3>
                                        <div class="calendar-container">
                                            <input type="text" id="datepicker" name="date" class="form-control custom-input" placeholder="Sélectionnez une date" required>
                                        </div>
                                        <button type="button" id="next-to-step2" class="btn btn-reservation mt-3" disabled>Suivant</button>
                                    </div>
                                    
                                    <div id="step2-content" style="display: none;">
                                        <h3 class="mb-4" style="color: #c1a068;">Choisissez une heure</h3>
                                        <div class="calendar-container">
                                            <div id="time-slots" class="text-center">
                                                <p>Veuillez d'abord sélectionner une date.</p>
                                            </div>
                                            <input type="hidden" id="time-input" name="heure" required>
                                        </div>
                                        <div class="d-flex justify-content-between mt-3">
                                            <button type="button" id="back-to-step1" class="btn btn-reservation">Précédent</button>
                                            <button type="button" id="next-to-step3" class="btn btn-reservation" disabled>Suivant</button>
                                        </div>
                                    </div>
                                    
                                    <div id="step3-content" style="display: none;">
                                        <h3 class="mb-4" style="color: #c1a068;">Informations complémentaires</h3>
                                        
                                        <?php if (!empty($user_info)): ?>
                                            <?php foreach ($user_info as $field => $value): ?>
                                                <div class="input-group mb-4">
                                                    <span class="input-group-text"><?php echo ucfirst($field); ?></span>
                                                    <input type="text" class="form-control custom-input" value="<?php echo htmlspecialchars($value); ?>" disabled>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        
                                        <div class="input-group mb-4">
                                            <span class="input-group-text">Nombre de personnes</span>
                                            <select name="personnes" class="form-control custom-input" required>
                                                <?php for($i = 1; $i <= 8; $i++): ?>
                                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="input-group mb-4">
                                            <span class="input-group-text">Demandes spéciales</span>
                                            <textarea name="description" class="form-control custom-input" rows="3" placeholder="Allergies, occasion spéciale, demandes particulières..."></textarea>
                                        </div>
                                        
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i> En confirmant cette réservation, vous acceptez nos conditions générales. Toute annulation doit être effectuée au minimum 24h à l'avance.
                                        </div>
                                        
                                        <div class="d-flex justify-content-between mt-3">
                                            <button type="button" id="back-to-step2" class="btn btn-reservation">Précédent</button>
                                            <button type="submit" class="btn btn-reservation">Confirmer la réservation</button>
                                        </div>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/l10n/fr.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Variables pour les étapes
        const step1 = document.getElementById('step1');
        const step2 = document.getElementById('step2');
        const step3 = document.getElementById('step3');
        
        const step1Content = document.getElementById('step1-content');
        const step2Content = document.getElementById('step2-content');
        const step3Content = document.getElementById('step3-content');
        
        // Boutons de navigation
        const nextToStep2 = document.getElementById('next-to-step2');
        const backToStep1 = document.getElementById('back-to-step1');
        const nextToStep3 = document.getElementById('next-to-step3');
        const backToStep2 = document.getElementById('back-to-step2');
        
        // Désactiver le bouton suivant jusqu'à ce qu'une date soit sélectionnée
        if (nextToStep2) {
            nextToStep2.disabled = true;
        }
        
        // Initialisation de flatpickr (sélecteur de date)
        const datePicker = flatpickr("#datepicker", {
            locale: "fr",
            dateFormat: "Y-m-d",
            minDate: "today",
            disableMobile: "true",
            onChange: function(selectedDates, dateStr) {
                if (selectedDates.length > 0) {
                    nextToStep2.disabled = false;
                    loadTimeSlots(dateStr);
                } else {
                    nextToStep2.disabled = true;
                }
            }
        });
        
        // Navigation entre les étapes
        nextToStep2.addEventListener('click', function() {
            step1Content.style.display = 'none';
            step2Content.style.display = 'block';
            
            step1.classList.remove('step-active');
            step2.classList.add('step-active');
        });
        
        backToStep1.addEventListener('click', function() {
            step2Content.style.display = 'none';
            step1Content.style.display = 'block';
            
            step2.classList.remove('step-active');
            step1.classList.add('step-active');
        });
        
        nextToStep3.addEventListener('click', function() {
            if (validateTimeSelection()) {
                step2Content.style.display = 'none';
                step3Content.style.display = 'block';
                
                step2.classList.remove('step-active');
                step3.classList.add('step-active');
            }
        });
        
        backToStep2.addEventListener('click', function() {
            step3Content.style.display = 'none';
            step2Content.style.display = 'block';
            
            step3.classList.remove('step-active');
            step2.classList.add('step-active');
        });
        
        // Gestion des créneaux horaires
        function loadTimeSlots(date) {
            const timeContainer = document.getElementById('time-slots');
            timeContainer.innerHTML = '<p>Chargement des créneaux disponibles...</p>';
            
            // Faire une vraie requête AJAX pour obtenir les créneaux disponibles
            fetch(`reservation.php?get_time_slots=1&date=${date}`)
                .then(response => response.json())
                .then(data => {
                    timeContainer.innerHTML = '';
                    
                    if (!data.slots || data.slots.length === 0) {
                        timeContainer.innerHTML = '<p>Aucun créneau disponible pour cette date</p>';
                        return;
                    }
                    
                    data.slots.forEach(slot => {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'time-slot';
                        button.textContent = slot;
                        button.addEventListener('click', function() {
                            // Désélectionner tous les autres boutons
                            document.querySelectorAll('.time-slot').forEach(btn => {
                                btn.classList.remove('selected');
                            });
                            
                            // Sélectionner celui-ci
                            button.classList.add('selected');
                            
                            // Mettre à jour la valeur d'entrée cachée
                            document.getElementById('time-input').value = slot;
                            
                            // Activer le bouton suivant
                            nextToStep3.disabled = false;
                        });
                        
                        timeContainer.appendChild(button);
                    });
                })
                .catch(error => {
                    timeContainer.innerHTML = '<p>Une erreur est survenue lors du chargement des créneaux</p>';
                    console.error('Erreur:', error);
                });
        }
        
        // Validation de la sélection de l'heure
        function validateTimeSelection() {
            const timeValue = document.getElementById('time-input').value;
            
            if (!timeValue) {
                alert('Veuillez sélectionner un créneau horaire');
                return false;
            }
            
            return true;
        }
    });
    </script>
</body>
</html>