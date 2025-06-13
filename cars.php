<?php
session_start();
include 'includes/config.php';
include 'includes/functions.php';

// Handle filters
$whereClause = "WHERE 1=1";
$params = [];
$types = "";

if (isset($_GET['marque']) && !empty($_GET['marque'])) {
    $whereClause .= " AND marque = ?";
    $params[] = $_GET['marque'];
    $types .= "s";
}

if (isset($_GET['type']) && !empty($_GET['type'])) {
    $whereClause .= " AND type = ?";
    $params[] = $_GET['type'];
    $types .= "s";
}

if (isset($_GET['prix_min']) && !empty($_GET['prix_min'])) {
    $whereClause .= " AND prix_par_jour >= ?";
    $params[] = $_GET['prix_min'];
    $types .= "d";
}

if (isset($_GET['prix_max']) && !empty($_GET['prix_max'])) {
    $whereClause .= " AND prix_par_jour <= ?";
    $params[] = $_GET['prix_max'];
    $types .= "d";
}

// Prepare the query
$query = "SELECT * FROM VOITURE $whereClause ORDER BY prix_par_jour ASC";
$stmt = mysqli_prepare($conn, $query);

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
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
                                $marqueQuery = "SELECT DISTINCT marque FROM VOITURE ORDER BY marque";
                                $marqueResult = mysqli_query($conn, $marqueQuery);
                                while ($marque = mysqli_fetch_assoc($marqueResult)) {
                                    $selected = (isset($_GET['marque']) && $_GET['marque'] == $marque['marque']) ? 'selected' : '';
                                    echo '<option value="'.$marque['marque'].'" '.$selected.'>'.$marque['marque'].'</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="type">Type</label>
                            <select name="type" id="type">
                                <option value="">Tous les types</option>
                                <option value="diesel" <?php echo (isset($_GET['type']) && $_GET['type'] == 'diesel') ? 'selected' : ''; ?>>Diesel</option>
                                <option value="essence" <?php echo (isset($_GET['type']) && $_GET['type'] == 'essence') ? 'selected' : ''; ?>>Essence</option>
                                <option value="hybride" <?php echo (isset($_GET['type']) && $_GET['type'] == 'hybride') ? 'selected' : ''; ?>>Hybride</option>
                                <option value="electrique" <?php echo (isset($_GET['type']) && $_GET['type'] == 'electrique') ? 'selected' : ''; ?>>Électrique</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="prix_min">Prix minimum (€/jour)</label>
                            <input type="number" name="prix_min" id="prix_min" min="0" value="<?php echo $_GET['prix_min'] ?? ''; ?>">
                        </div>
                        <div class="filter-group">
                            <label for="prix_max">Prix maximum (€/jour)</label>
                            <input type="number" name="prix_max" id="prix_max" min="0" value="<?php echo $_GET['prix_max'] ?? ''; ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">Appliquer les filtres</button>
                        <a href="cars.php" class="btn btn-outline">Réinitialiser</a>
                    </form>
                </div>
                <div class="cars-list">
                    <div class="results-count">
                        <p><i class="fas fa-car"></i> <?php echo mysqli_num_rows($result); ?> voiture(s) trouvée(s)</p>
                        <div class="sort-options">
                            <span>Trier par:</span>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price_asc'])); ?>" class="<?php echo (isset($_GET['sort']) && $_GET['sort'] == 'price_asc') ? 'active' : ''; ?>">Prix croissant</a>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price_desc'])); ?>" class="<?php echo (isset($_GET['sort']) && $_GET['sort'] == 'price_desc') ? 'active' : ''; ?>">Prix décroissant</a>
                        </div>
                    </div>
                    <div class="cars-grid">
                        <?php
                        if (mysqli_num_rows($result) > 0) {
                            while ($car = mysqli_fetch_assoc($result)) {
                                include 'includes/car-card.php';
                            }
                        } else {
                            echo '<div class="no-results"><i class="fas fa-search"></i>Aucune voiture ne correspond à vos critères.</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/main.js"></script>
</body>
</html>