<?php
// Inclure le fichier de configuration
require_once 'config.php';


?>
<!DOCTYPE html>
<html lang="fr">
<!-- Le reste de votre code HTML actuel -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Le Gourmet Nomade</title>
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
                <a class="navbar-brand" href="#accueil">
                    <img src="assets/img/logoResto.png" alt="Logo" width="120" height="120" class="d-inline-block align-text-top rounded">
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item"><a class="nav-link" href="#photos">Photos</a></li>
                        <li class="nav-item"><a class="nav-link" href="#menu">Menu</a></li>
                        <li class="nav-item"><a class="nav-link" href="#about">À Propos</a></li>
                    </ul>
                    <a href="#reservation" class="btn btn-reservation ms-3">Réserver</a>
                </div>
            </div>
        </nav>
    </header>

    <main class="pt-5">
        <section id="photos" class="min-vh-100 d-flex justify-content-center align-items-center flex-column text-center py-5">
            <p class="fs-1 mb-5" style="color: #c1a068;">LE RESTAURANT EN PHOTO</p>
            <article class="container mt-5">
                <div class="row justify-content-center g-4">
                    <div class="col-md-4 d-flex justify-content-center">
                        <img class="img-fluid rounded-3 shadow-lg img-hover" src="assets/img/resto1.jpeg" alt="Photo restaurant">
                    </div>
                    <div class="col-md-4 d-flex justify-content-center">
                        <img class="img-fluid rounded-3 shadow-lg img-hover" src="assets/img/resto2.jpg" alt="Photo restaurant">
                    </div>
                    <div class="col-md-4 d-flex justify-content-center">
                        <img class="img-fluid rounded-3 shadow-lg img-hover" src="assets/img/resto3.jpg" alt="Photo restaurant">
                    </div>
                </div>
            </article>
        </section>

        <section id="about" class="min-vh-100 d-flex justify-content-center align-items-center flex-column text-center py-5">
            <h1 class="text-white display-3" style="color: #c1a068;">Bienvenue chez Le Gourmet Nomade</h1>
            <p class="text-white lead fs-2">Découvrez une expérience culinaire unique.</p>
            <img src="assets/img/logoResto.png" alt="Logo" width="200" height="200" class="d-inline-block align-text-top rounded">
            <article class="mt-5 px-3 col-md-9">
                <h3 class="text-white fs-4">
                    <i class="fa-solid fa-quote-left fa-1x me-5" style="color: #c1a068;"></i>
                    Chez <strong>Le Gourmet Nomade</strong>, chaque plat est une invitation au voyage. Nous transformons des ingrédients 
                    simples en une expérience culinaire inoubliable, mêlant tradition et créativité. Parce que la gastronomie 
                    n'est pas seulement une question de goût, mais une émotion à partager, nous mettons tout notre savoir-faire 
                    pour éveiller vos sens et ravir votre palais.  
                    <i class="fa-solid fa-quote-right fa-1x ms-5" style="color: #c1a068;"></i>
                </h3>
            </article>
        </section>

        <section id="menu" class="min-vh-100 d-flex justify-content-center align-items-center flex-column py-5">
            <p class="fs-1 mb-5" style="color: #c1a068;">NOTRE CARTE</p>
            <div class="container mt-5">
                <div class="row justify-content-center g-4">
                    <div class="col-md-4">
                        <div class="card bg-dark border border-2 p-3" style="border-radius: 20px;" >
                            <div id="carousel1" class="carousel slide" style="border-radius: 10px; overflow: hidden;">
                                <div class="carousel-inner">
                                    <div class="carousel-item active">
                                        <img src="assets/img/E1.jpg" class="d-block w-100" alt="Plat 1" style="height: 300px; object-fit: cover;">
                                    </div>
                                    <div class="carousel-item">
                                        <img src="assets/img/E2.jpg" class="d-block w-100" alt="Plat 2" style="height: 300px; object-fit: cover;">
                                    </div>
                                    <div class="carousel-item">
                                        <img src="assets/img/E3.jpg" class="d-block w-100" alt="Plat 3" style="height: 300px; object-fit: cover;">
                                    </div>
                                </div>
                                <button class="carousel-control-prev" type="button" data-bs-target="#carousel1" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Previous</span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#carousel1" data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Next</span>
                                </button>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title" style="color: #c1a068;">Nos Entrées</h5>
                                <p class="card-text text-white">Découvrez nos entrées raffinées, préparées avec des produits de saison.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-dark border border-2 p-3" style="border-radius: 20px;">
                            <div id="carousel2" class="carousel slide" style="border-radius: 10px; overflow: hidden;">
                                <div class="carousel-inner">
                                    <div class="carousel-item active">
                                        <img src="assets/img/P1.jpg" class="d-block w-100" alt="Plat 1" style="height: 300px; object-fit: cover;">
                                    </div>
                                    <div class="carousel-item">
                                        <img src="assets/img/P2.jpg" class="d-block w-100" alt="Plat 2" style="height: 300px; object-fit: cover;">
                                    </div>
                                    <div class="carousel-item">
                                        <img src="assets/img/P3.jpg" class="d-block w-100" alt="Plat 3" style="height: 300px; object-fit: cover;">
                                    </div>
                                </div>
                                <button class="carousel-control-prev" type="button" data-bs-target="#carousel2" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Previous</span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#carousel2" data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Next</span>
                                </button>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title" style="color: #c1a068;">Nos Plats</h5>
                                <p class="card-text text-white">Une sélection de plats principaux mêlant tradition et innovation.</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card bg-dark border border-2 p-3" style="border-radius: 20px;" >
                            <div id="carousel3" class="carousel slide" style="border-radius: 10px; overflow: hidden;">
                                <div class="carousel-inner">
                                    <div class="carousel-item active">
                                        <img src="assets/img/D1.jpg" class="d-block w-100" alt="Plat 1" style="height: 300px; object-fit: cover;">
                                    </div>
                                    <div class="carousel-item">
                                        <img src="assets/img/D2.jpg" class="d-block w-100" alt="Plat 2" style="height: 300px; object-fit: cover;">
                                    </div>
                                    <div class="carousel-item">
                                        <img src="assets/img/D3.jpg" class="d-block w-100" alt="Plat 3" style="height: 300px; object-fit: cover;">
                                    </div>
                                </div>
                                <button class="carousel-control-prev" type="button" data-bs-target="#carousel3" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Previous</span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#carousel3" data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Next</span>
                                </button>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title" style="color: #c1a068;">Nos Desserts</h5>
                                <p class="card-text text-white">Terminez votre repas en beauté avec nos créations sucrées.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="min-vh-100 d-flex justify-content-center align-items-center flex-column py-5">
            <div class="col-md-6 d-flex flex-column align-items-center">
                <p class="fs-1 mb-5" style="color: #c1a068;">LOCALISATION</p>
                <article class="container mt-5">
                    <div class="row justify-content-center">
                        <div class="d-flex justify-content-center">
                            <img class="img-fluid rounded-3 shadow-lg img-hover" src="assets/img/localisation.JPG" alt="Photo restaurant">
                        </div>
                    </div>
                </article>
            </div>
        </section>

        <section id="reservation" class="min-vh-100 d-flex justify-content-center align-items-center flex-column py-5">
            <p class="fs-1 mb-5" style="color: #c1a068;">RESERVATION</p>
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="p-4 rounded-3 bg-dark bg-opacity-50">
                            <div class="input-group mb-5">
                                <input type="text" class="form-control custom-input" placeholder="Username" aria-label="Username">
                            </div>
                            <div class="input-group mb-5">
                                <input type="text" class="form-control custom-input" placeholder="0123456789" aria-label="Téléphone">
                            </div>
                            <div class="input-group mb-5">
                                <input type="email" class="form-control custom-input" placeholder="Email" aria-label="Email" aria-describedby="basic-addon2">
                                <span class="input-group-text" id="basic-addon2">@example.com</span>
                            </div>
                            <div class="input-group mb-5">
                                <span class="input-group-text">Date</span>
                                <input type="date" class="form-control custom-input">
                            </div>
                            <div class="input-group mb-5 w-100">
                                <span class="input-group-text">Description</span>
                                <textarea class="form-control custom-input" aria-label="With textarea"></textarea>
                            </div>
                            <div class="text-center">
                                <a href="reservation.php" class="btn btn-reservation">Réserver</a>
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