<?php
session_start();
include 'includes/config.php';
include 'includes/functions.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nos Services - AutoDrive</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <section class="page-header">
        <div class="container">
            <h1>Nos Services</h1>
            <p>Découvrez tous les services que nous proposons pour votre confort</p>
        </div>
    </section>

    <section class="services">
        <div class="container">
            <div class="services-grid">
                <div class="service-card">
                    <div class="icon">
                        <i class="fas fa-car"></i>
                    </div>
                    <h3>Location de Voitures</h3>
                    <p>Large gamme de véhicules pour tous vos besoins de déplacement, des citadines aux utilitaires.</p>
                </div>
                
                <div class="service-card">
                    <div class="icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3>Location Courte Durée</h3>
                    <p>Solutions flexibles pour vos besoins ponctuels, de quelques heures à plusieurs jours.</p>
                </div>
                
                <div class="service-card">
                    <div class="icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3>Location Longue Durée</h3>
                    <p>Forfaits avantageux pour des locations de plusieurs semaines ou mois.</p>
                </div>
                
                <div class="service-card">
                    <div class="icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Assurance Tous Risques</h3>
                    <p>Protection complète pour une conduite en toute sérénité.</p>
                </div>
                
                <div class="service-card">
                    <div class="icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <h3>GPS Inclus</h3>
                    <p>Système de navigation GPS intégré dans tous nos véhicules.</p>
                </div>
                
                <div class="service-card">
                    <div class="icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <h3>Maintenance Incluse</h3>
                    <p>Entretien régulier et assistance technique 24/7.</p>
                </div>
                
                <div class="service-card">
                    <div class="icon">
                        <i class="fas fa-child"></i>
                    </div>
                    <h3>Siège Auto</h3>
                    <p>Sièges auto et rehausseurs disponibles sur demande.</p>
                </div>
                
                <div class="service-card">
                    <div class="icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3>Support 24/7</h3>
                    <p>Une équipe à votre écoute 24h/24 et 7j/7.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="cta">
        <div class="container">
            <div class="cta-content">
                <h2>Prêt à réserver ?</h2>
                <p>Découvrez notre flotte de véhicules et trouvez celui qui vous convient</p>
                <a href="cars.php" class="btn btn-light">Voir nos voitures</a>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/main.js"></script>
</body>
</html>