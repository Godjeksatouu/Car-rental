
<?php
session_start();
include 'includes/config.php';
include 'includes/functions.php';

// Simple query to get all cars - no filters for now
$query = "SELECT * FROM voiture ORDER BY prix_par_jour ASC";
$result = mysqli_query($conn, $query);

// Check if query was successful
if (!$result) {
    echo "Error: " . mysqli_error($conn);
}

// Count the number of cars
$carCount = $result ? mysqli_num_rows($result) : 0;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nos Voitures - AutoDrive</title>
    <link rel="stylesheet" href="assets/css/style.css">
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
            <div class="cars-content">
                <div class="filters">
                    <h3><i class="fas fa-filter"></i> Filtres</h3>
                    <form action="cars.php" method="GET">
                        <div class="filter-group">
                            <label for="marque">Marque</label>
                            <select name="marque" id="marque">
                                <option value="">Toutes les marques</option>
                                <?php
                                $marqueQuery = "SELECT DISTINCT marque FROM voiture ORDER BY marque";
                                $marqueResult = mysqli_query($conn, $marqueQuery);
                                if ($marqueResult) {
                                    while ($marque = mysqli_fetch_assoc($marqueResult)) {
                                        echo '<option value="' . htmlspecialchars($marque['marque']) . '">' . htmlspecialchars($marque['marque']) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="type">Type</label>
                            <select name="type" id="type">
                                <option value="">Tous les types</option>
                                <option value="diesel">Diesel</option>
                                <option value="essence">Essence</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="prix_min">Prix minimum (€/jour)</label>
                            <input type="number" name="prix_min" id="prix_min" min="0">
                        </div>
                        <div class="filter-group">
                            <label for="prix_max">Prix maximum (€/jour)</label>
                            <input type="number" name="prix_max" id="prix_max" min="0">
                        </div>
                        <button type="submit" class="btn btn-primary">Appliquer les filtres</button>
                        <a href="cars.php" class="btn btn-outline">Réinitialiser</a>
                    </form>
                </div>

                <div class="cars-list">
                    <div class="results-count">
                        <p><i class="fas fa-car"></i> <?php echo $carCount; ?> voiture(s) trouvée(s)</p>
                    </div>

                    <div class="cars-grid" id="cars-grid">
                        <?php
                        if ($result && mysqli_num_rows($result) > 0) {
                            while ($car = mysqli_fetch_assoc($result)) {
                                ?>
                                <div class="car-card" 
                                     data-marque="<?php echo htmlspecialchars($car['marque']); ?>" 
                                     data-type="<?php echo htmlspecialchars($car['type']); ?>" 
                                     data-prix="<?php echo (float)$car['prix_par_jour']; ?>">
                                    <div class="car-image">
                                        <?php
                                        $image = $car['image'] && $car['image'] != '0' ? $car['image'] : 'https://images.pexels.com/photos/170811/pexels-photo-170811.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1';
                                        ?>
                                        <img src="<?php echo $image; ?>" alt="<?php echo $car['marque'] . ' ' . $car['modele']; ?>">
                                        <div class="car-status <?php echo $car['statut']; ?>"><?php echo ucfirst($car['statut']); ?></div>
                                    </div>
                                    <div class="car-info">
                                        <div class="car-title">
                                            <h3><?php echo $car['marque'] . ' ' . $car['modele']; ?></h3>
                                            <p class="car-immatriculation"><?php echo $car['immatriculation']; ?></p>
                                        </div>
                                        <div class="car-features">
                                            <div class="feature">
                                                <i class="fas fa-gas-pump"></i>
                                                <span><?php echo ucfirst($car['type']); ?></span>
                                            </div>
                                            <div class="feature">
                                                <i class="fas fa-users"></i>
                                                <span><?php echo $car['nb_places']; ?> places</span>
                                            </div>
                                            <div class="feature">
                                                <i class="fas fa-tachometer-alt"></i>
                                                <span><?php echo ucfirst($car['type']); ?></span>
                                            </div>
                                        </div>
                                        <div class="car-price">
                                            <div class="price-tag">
                                                <span class="price"><?php echo $car['prix_par_jour']; ?> €</span>
                                                <span class="period">/ jour</span>
                                            </div>
                                            <a href="reservation.php?id=<?php echo $car['id_voiture']; ?>" class="btn btn-success">
                                                <i class="fas fa-calendar-check"></i> Réserver
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }
                        } else {
                            echo '<div class="no-results"><i class="fas fa-search"></i> Aucune voiture disponible.</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    
    <script src="assets/js/main.js"></script>
    <script>
        // Reset filter button behavior
        document.getElementById('reset-filters').addEventListener('click', () => {
            document.getElementById('filter-form').reset();
            document.getElementById('filter-form').dispatchEvent(new Event('submit'));
        });
    </script>
</body>
</html>
