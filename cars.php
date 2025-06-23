<?php
session_start();
include 'includes/config.php';
include 'includes/functions.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get filter values from URL
$marque = isset($_GET['marque']) ? $_GET['marque'] : '';
$type = isset($_GET['type']) ? $_GET['type'] : '';
$gear = isset($_GET['gear']) ? $_GET['gear'] : '';
$prix_min = isset($_GET['prix_min']) ? $_GET['prix_min'] : 0;
$prix_max = isset($_GET['prix_max']) ? $_GET['prix_max'] : 0;
$places = isset($_GET['nb_places']) ? $_GET['nb_places'] : 0;

// Build SQL query
$sql = "SELECT * FROM voiture WHERE 1=1";

if (!empty($marque)) {
    $sql .= " AND marque = '$marque'";
}

if (!empty($type)) {
    $sql .= " AND type = '$type'";
}

if (!empty($gear)) {
    $sql .= " AND gear = '$gear'";
}

if ($prix_min > 0) {
    $sql .= " AND prix_par_jour >= $prix_min";
}

if ($prix_max > 0) {
    $sql .= " AND prix_par_jour <= $prix_max";
}

if ($places > 0) {
    $sql .= " AND nb_places = $places";
}

$sql .= " ORDER BY prix_par_jour ASC";

// Execute query
$result = mysqli_query($conn, $sql);
$carCount = mysqli_num_rows($result);

// Get filter options
$marques = mysqli_query($conn, "SELECT DISTINCT marque FROM voiture ORDER BY marque");
$types = mysqli_query($conn, "SELECT DISTINCT type FROM voiture ORDER BY type");
$gears = mysqli_query($conn, "SELECT DISTINCT gear FROM voiture ORDER BY gear");
$places_options = mysqli_query($conn, "SELECT DISTINCT nb_places FROM voiture ORDER BY nb_places");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nos Voitures - AutoDrive</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/cars.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <section class="page-header">
        <div class="container">
            <h1>Nos Voitures</h1>
            <p>Trouvez la voiture parfaite pour vos besoins</p>
        </div>
    </section>

    <section class="cars-section">
        <div class="container">      
            
            <!-- Filter Section -->
            <div class="filters-section">
                <h3><i class="fas fa-filter"></i> Filtrer les voitures</h3>
                <form method="GET" action="cars.php" class="filters-form">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="marque">Marque</label>
                            <select name="marque" id="marque">
                                <option value="">Toutes les marques</option>
                                <?php while($marque_row = mysqli_fetch_assoc($marques)): ?>
                                    <option value="<?= htmlspecialchars($marque_row['marque']) ?>" <?= ($marque === $marque_row['marque']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($marque_row['marque']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="type">Type de carburant</label>
                            <select name="type" id="type">
                                <option value="">Tous les types</option>
                                <?php while($type_row = mysqli_fetch_assoc($types)): ?>
                                    <option value="<?= htmlspecialchars($type_row['type']) ?>" <?= ($type === $type_row['type']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($type_row['type']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="gear">Boîte de vitesses</label>
                            <select name="gear" id="gear">
                                <option value="">Toutes les boîtes</option>
                                <?php while($gear_row = mysqli_fetch_assoc($gears)): ?>
                                    <option value="<?= htmlspecialchars($gear_row['gear']) ?>" <?= ($gear === $gear_row['gear']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($gear_row['gear']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="nb_places">Nombre de places</label>
                            <select name="nb_places" id="nb_places">
                                <option value="">Toutes</option>
                                <?php while($places_row = mysqli_fetch_assoc($places_options)): ?>
                                    <option value="<?= $places_row['nb_places'] ?>" <?= ($places == $places_row['nb_places']) ? 'selected' : '' ?>>
                                        <?= $places_row['nb_places'] ?> places
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="prix_min">Prix minimum (€/jour)</label>
                            <input type="number" name="prix_min" id="prix_min" min="0" step="10"
                                   value="<?= $prix_min > 0 ? $prix_min : '' ?>" placeholder="0">
                        </div>

                        <div class="filter-group">
                            <label for="prix_max">Prix maximum (€/jour)</label>
                            <input type="number" name="prix_max" id="prix_max" min="0" step="10"
                                   value="<?= $prix_max > 0 ? $prix_max : '' ?>" placeholder="1000">
                        </div>

                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Filtrer
                            </button>
                            <a href="cars.php" class="btn btn-outline">
                                <i class="fas fa-undo"></i> Réinitialiser
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Results Section -->
            <div class="results-count">
                <p><i class="fas fa-car"></i> <?= $carCount ?> voiture(s) trouvée(s)</p>
                <?php if (!empty($marque) || !empty($type) || !empty($gear) || $prix_min > 0 || $prix_max > 0 || $places > 0): ?>
                    <p class="active-filters">
                        <i class="fas fa-filter"></i> Filtres actifs:
                        <?php
                        $active_filters = [];
                        if (!empty($marque)) $active_filters[] = "Marque: $marque";
                        if (!empty($type)) $active_filters[] = "Type: $type";
                        if (!empty($gear)) $active_filters[] = "Boîte: $gear";
                        if ($prix_min > 0) $active_filters[] = "Prix min: {$prix_min}€";
                        if ($prix_max > 0) $active_filters[] = "Prix max: {$prix_max}€";
                        if ($places > 0) $active_filters[] = "Places: $places";
                        echo implode(", ", $active_filters);
                        ?>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Cars Grid -->
            <div class="cars-grid">
                <?php if ($carCount > 0): ?>
                    <?php while($car = mysqli_fetch_assoc($result)): ?>
                        <div class="car-card">
                            <div class="car-image">
                                <img src="<?= htmlspecialchars($car['image'] ?: 'no-image.jpg') ?>" alt="<?= htmlspecialchars($car['marque'] . ' ' . $car['modele']) ?>">
                                <div class="car-status <?= $car['statut'] ?>"><?= ucfirst($car['statut']) ?></div>
                            </div>

                            <div class="car-info">
                                <div class="car-title">
                                    <h3><?= htmlspecialchars($car['marque'] . ' ' . $car['modele']) ?></h3>
                                    <p class="car-immatriculation"><?= htmlspecialchars($car['immatriculation']) ?></p>
                                </div>

                                <div class="car-features">
                                    <div class="feature">
                                        <i class="fas fa-gas-pump"></i>
                                        <span><?= htmlspecialchars($car['type']) ?></span>
                                    </div>
                                    <div class="feature">
                                        <i class="fas fa-users"></i>
                                        <span><?= $car['nb_places'] ?> places</span>
                                    </div>
                                    <div class="feature">
                                        <i class="fas fa-cogs"></i>
                                        <span><?= htmlspecialchars($car['gear']) ?></span>
                                    </div>
                                </div>

                                <div class="car-price">
                                    <div class="price-tag">
                                        <span class="price"><?= $car['prix_par_jour'] ?> €</span>
                                        <span class="period">/ jour</span>
                                    </div>
                                    <a href="reservation.php?id=<?= $car['id_voiture'] ?>" class="btn btn-success">
                                        <i class="fas fa-calendar-check"></i> Réserver
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-results"><i class="fas fa-search"></i> Aucune voiture disponible avec ces critères.</div>
                <?php endif; ?>
            </div>
        </div>
    </section>

     <?php include 'includes/footer.php'; ?>
    <script src="assets/js/main.js"></script>

    <?php mysqli_close($conn); ?>

</body>
</html>