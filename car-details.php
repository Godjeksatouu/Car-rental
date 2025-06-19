<?php
session_start();
include 'includes/config.php';
include 'includes/functions.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirectWithMessage('cars.php', 'Identifiant de voiture invalide', 'error');
}

$carId = (int)$_GET['id'];
$query = "SELECT * FROM VOITURE WHERE id_voiture = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $carId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    redirectWithMessage('cars.php', 'Voiture non trouvée', 'error');
}

$car = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $car['marque'] . ' ' . $car['modele']; ?> - AutoDrive</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <section class="car-details">
        <div class="container">
            <div class="car-details-content">
                <div class="car-details-images">
                    <?php
                    $image = $car['image'] ? $car['image'] : 'https://images.pexels.com/photos/170811/pexels-photo-170811.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1';
                    ?>
                    <div class="main-image">
                        <img src="<?php echo $image; ?>" alt="<?php echo $car['marque'] . ' ' . $car['modele']; ?>">
                        <div class="car-status <?php echo $car['statut']; ?>"><?php echo ucfirst($car['statut']); ?></div>
                    </div>
                </div>
                <div class="car-details-info">
                    <div class="car-title">
                        <h1><?php echo $car['marque'] . ' ' . $car['modele']; ?></h1>
                        <div class="car-price">
                            <span class="price"><?php echo $car['prix_par_jour']; ?> €</span>
                            <span class="period">/ jour</span>
                        </div>
                    </div>
                    <div class="car-specs">
                        <div class="spec">
                            <div class="icon"><i class="fas fa-gas-pump"></i></div>
                            <div class="text">
                                <span class="label">Type</span>
                                <span class="value"><?php echo ucfirst($car['type']); ?></span>
                            </div>
                        </div>
                        <div class="spec">
                            <div class="icon"><i class="fas fa-users"></i></div>
                            <div class="text">
                                <span class="label">Places</span>
                                <span class="value"><?php echo $car['nb_places']; ?></span>
                            </div>
                        </div>
                        <div class="spec">
                            <div class="icon"><i class="fas fa-tachometer-alt"></i></div>
                            <div class="text">
                                <span class="label">Carburant</span>
                                <span class="value"><?php echo ucfirst($car['carburant']); ?></span>
                            </div>
                        </div>
                        <div class="spec">
                            <div class="icon"><i class="fas fa-id-card"></i></div>
                            <div class="text">
                                <span class="label">Immatriculation</span>
                                <span class="value"><?php echo $car['immatriculation']; ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="car-description">
                        <h3>Description</h3>
                        <p>Cette <?php echo $car['marque'] . ' ' . $car['modele']; ?> est un véhicule moderne et fiable, parfait pour vos déplacements quotidiens ou vos voyages. Elle est équipée de toutes les fonctionnalités nécessaires pour vous assurer confort et sécurité.</p>
                    </div>
                    <div class="car-actions">
                        <?php if ($car['statut'] === 'disponible'): ?>
                            <?php if (isLoggedIn()): ?>
                                <a href="reservation.php?id=<?php echo $car['id_voiture']; ?>" class="btn btn-primary">Réserver maintenant</a>
                            <?php else: ?>
                                <a href="login.php?redirect=car-details.php?id=<?php echo $car['id_voiture']; ?>" class="btn btn-primary">Connexion pour réserver</a>
                            <?php endif; ?>
                        <?php else: ?>
                            <button class="btn btn-disabled" disabled>Non disponible</button>
                        <?php endif; ?>
                        <a href="cars.php" class="btn btn-outline">Retour aux voitures</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="similar-cars">
        <div class="container">
            <div class="section-header">
                <h2>Voitures similaires</h2>
                <p>Découvrez d'autres véhicules qui pourraient vous intéresser</p>
            </div>
            <div class="cars-grid">
                <?php
                $query = "SELECT * FROM VOITURE WHERE marque = ? AND id_voiture != ? AND statut = 'disponible' LIMIT 3";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "si", $car['marque'], $carId);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                if (mysqli_num_rows($result) > 0) {
                    while ($similarCar = mysqli_fetch_assoc($result)) {
                        $car = $similarCar;
                        include 'includes/car-card.php';
                    }
                } else {
                    // If no cars with same brand, show other available cars
                    $query = "SELECT * FROM VOITURE WHERE id_voiture != ? AND statut = 'disponible' LIMIT 3";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "i", $carId);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    
                    while ($similarCar = mysqli_fetch_assoc($result)) {
                        $car = $similarCar;
                        include 'includes/car-card.php';
                    }
                }
                ?>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/main.js"></script>
</body>
</html>