<?php
session_start();
include 'includes/config.php';
include 'includes/functions.php';

// Enhanced error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Test database connection first
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Clear any existing query variables to prevent conflicts
unset($query, $result, $stmt);

// Initialize filter variables with better validation
$car_marque_filter = isset($_GET['marque']) && !empty(trim($_GET['marque'])) ? trim($_GET['marque']) : '';
$car_type_filter = isset($_GET['type']) && !empty(trim($_GET['type'])) ? trim($_GET['type']) : '';
$car_gear_filter = isset($_GET['gear']) && !empty(trim($_GET['gear'])) ? trim($_GET['gear']) : '';
$car_prix_min = isset($_GET['prix_min']) && is_numeric($_GET['prix_min']) && $_GET['prix_min'] > 0 ? (float)$_GET['prix_min'] : 0;
$car_prix_max = isset($_GET['prix_max']) && is_numeric($_GET['prix_max']) && $_GET['prix_max'] > 0 ? (float)$_GET['prix_max'] : 0;
$car_nb_places_filter = isset($_GET['nb_places']) && !empty(trim($_GET['nb_places'])) ? (int)trim($_GET['nb_places']) : 0;

// Build the WHERE clause based on filters
$car_where_conditions = [];
$car_params = [];
$car_param_types = '';

if (!empty($car_marque_filter)) {
    $car_where_conditions[] = "marque = ?";
    $car_params[] = $car_marque_filter;
    $car_param_types .= 's';
}

if (!empty($car_type_filter)) {
    $car_where_conditions[] = "type = ?";
    $car_params[] = $car_type_filter;
    $car_param_types .= 's';
}

if (!empty($car_gear_filter)) {
    $car_where_conditions[] = "gear = ?";
    $car_params[] = $car_gear_filter;
    $car_param_types .= 's';
}

if ($car_prix_min > 0) {
    $car_where_conditions[] = "prix_par_jour >= ?";
    $car_params[] = $car_prix_min;
    $car_param_types .= 'd';
}

if ($car_prix_max > 0) {
    $car_where_conditions[] = "prix_par_jour <= ?";
    $car_params[] = $car_prix_max;
    $car_param_types .= 'd';
}

if ($car_nb_places_filter > 0) {
    $car_where_conditions[] = "nb_places = ?";
    $car_params[] = $car_nb_places_filter;
    $car_param_types .= 'i';
}

// Build the complete query
$car_query = "SELECT id_voiture, marque, modele, immatriculation, type, carburant, image, nb_places, statut, prix_par_jour, gear FROM voiture";

if (!empty($car_where_conditions)) {
    $car_query .= " WHERE " . implode(" AND ", $car_where_conditions);
}

$car_query .= " ORDER BY prix_par_jour ASC";

// Debug: Show the actual car query and parameters
echo "<!-- CAR DEBUG QUERY: " . $car_query . " -->";
echo "<!-- CAR DEBUG PARAMS: " . implode(', ', $car_params) . " -->";
echo "<!-- CAR DEBUG PARAM TYPES: " . $car_param_types . " -->";

// Prepare and execute the query
$car_result = null;
if (!empty($car_params)) {
    $car_stmt = mysqli_prepare($conn, $car_query);
    if ($car_stmt) {
        if (!empty($car_param_types)) {
            mysqli_stmt_bind_param($car_stmt, $car_param_types, ...$car_params);
        }
        $car_execute_result = mysqli_stmt_execute($car_stmt);
        if ($car_execute_result) {
            $car_result = mysqli_stmt_get_result($car_stmt);
        } else {
            echo "<!-- CAR DEBUG: Execute failed: " . mysqli_stmt_error($car_stmt) . " -->";
        }
    } else {
        echo "<!-- CAR DEBUG: Prepare failed: " . mysqli_error($conn) . " -->";
    }
} else {
    $car_result = mysqli_query($conn, $car_query);
    if (!$car_result) {
        echo "<!-- CAR DEBUG: Query failed: " . mysqli_error($conn) . " -->";
    }
}

// Check if query was successful
if (!$car_result) {
    echo "Database Error: " . mysqli_error($conn);
    die();
}

// Count the number of cars
$carCount = mysqli_num_rows($car_result);

// Get unique values for filter dropdowns - these should be separate queries
$marques_query = "SELECT DISTINCT marque FROM voiture WHERE marque IS NOT NULL AND marque != '' ORDER BY marque";
$marques_result = mysqli_query($conn, $marques_query);

$types_query = "SELECT DISTINCT type FROM voiture WHERE type IS NOT NULL AND type != '' ORDER BY type";
$types_result = mysqli_query($conn, $types_query);

$gears_query = "SELECT DISTINCT gear FROM voiture WHERE gear IS NOT NULL AND gear != '' ORDER BY gear";
$gears_result = mysqli_query($conn, $gears_query);

$places_query = "SELECT DISTINCT nb_places FROM voiture WHERE nb_places IS NOT NULL AND nb_places > 0 ORDER BY nb_places";
$places_result = mysqli_query($conn, $places_query);

// Enhanced debug information
echo "<!-- CAR Debug: Connected to database successfully -->";
echo "<!-- CAR Debug: Query: " . $car_query . " -->";
echo "<!-- CAR Debug: Found " . $carCount . " cars -->";
echo "<!-- CAR Debug: Active filters - Marque: '" . $car_marque_filter . "', Type: '" . $car_type_filter . "', Gear: '" . $car_gear_filter . "', Prix: " . $car_prix_min . "-" . $car_prix_max . ", Places: " . $car_nb_places_filter . " -->";

// Test query without filters to see total cars
$total_cars_query = "SELECT COUNT(*) as total FROM voiture";
$total_cars_result = mysqli_query($conn, $total_cars_query);
if ($total_cars_result) {
    $total_cars_row = mysqli_fetch_assoc($total_cars_result);
    echo "<!-- CAR Debug: Total cars in database: " . $total_cars_row['total'] . " -->";
}

// Let's also check what data is actually in the database
$sample_data_query = "SELECT marque, type, statut, prix_par_jour, nb_places FROM voiture LIMIT 5";
$sample_data_result = mysqli_query($conn, $sample_data_query);
echo "<!-- CAR Debug: Sample data from database: -->";
if ($sample_data_result) {
    while ($sample_row = mysqli_fetch_assoc($sample_data_result)) {
        echo "<!-- " . json_encode($sample_row) . " -->";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nos Voitures - AutoDrive</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Debug styles - remove these after fixing */
        .cars-grid {
            display: grid !important;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px;
        }
        
        .car-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .car-card:hover {
            transform: translateY(-2px);
        }
        
        .car-image {
            position: relative;
            height: 200px;
            overflow: hidden;
        }
        
        .car-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .car-status {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .car-status.disponible {
            background: #28a745;
            color: white;
        }
        
        .car-status.réservé {
            background: #ffc107;
            color: #333;
        }
        
        .car-status.maintenance {
            background: #dc3545;
            color: white;
        }
        
        .car-info {
            padding: 15px;
        }
        
        .car-title h3 {
            margin: 0 0 5px 0;
            font-size: 1.2em;
            color: #333;
        }
        
        .car-immatriculation {
            color: #666;
            font-size: 0.9em;
            margin: 0 0 10px 0;
        }
        
        .car-features {
            display: flex;
            gap: 15px;
            margin: 10px 0;
        }
        
        .feature {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9em;
            color: #666;
        }
        
        .car-price {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .price-tag .price {
            font-size: 1.3em;
            font-weight: bold;
            color: #28a745;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9em;
            cursor: pointer;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-disabled {
            background: #6c757d;
            color: white;
            cursor: not-allowed;
        }
        
        .blocked-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }
        
        .blocked-content {
            color: white;
            text-align: center;
        }
        
        .no-results {
            text-align: center;
            padding: 40px;
            color: #666;
            grid-column: 1 / -1;
        }

        /* Filter Section Styles */
        .filters-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
        }

        .filters-section h3 {
            margin: 0 0 20px 0;
            color: #333;
            font-size: 1.3em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filters-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
            font-size: 0.9em;
        }

        .filter-group select,
        .filter-group input {
            padding: 10px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 0.9em;
            transition: border-color 0.3s ease;
            background: white;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
        }

        .filter-actions {
            display: flex;
            gap: 10px;
            align-items: end;
        }

        .filter-actions .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-actions .btn-primary {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
        }

        .filter-actions .btn-primary:hover {
            background: linear-gradient(135deg, #0056b3, #004085);
            transform: translateY(-2px);
        }

        .filter-actions .btn-outline {
            background: transparent;
            color: #6c757d;
            border: 2px solid #6c757d;
        }

        .filter-actions .btn-outline:hover {
            background: #6c757d;
            color: white;
        }

        .results-count {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }

        .results-count p {
            margin: 0;
            font-weight: 600;
            color: #333;
        }

        .active-filters {
            margin-top: 10px !important;
            font-size: 0.9em;
            color: #666;
            font-weight: normal !important;
        }



        /* Responsive Design */
        @media (max-width: 768px) {
            .filter-row {
                grid-template-columns: 1fr;
            }

            .filter-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-actions .btn {
                justify-content: center;
            }
        }
    </style>
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
                                <?php
                                if ($marques_result && mysqli_num_rows($marques_result) > 0) {
                                    while ($marque = mysqli_fetch_assoc($marques_result)) {
                                        $selected = ($car_marque_filter === $marque['marque']) ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($marque['marque']) . '" ' . $selected . '>' . htmlspecialchars($marque['marque']) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="type">Type de carburant</label>
                            <select name="type" id="type">
                                <option value="">Tous les types</option>
                                <?php
                                if ($types_result && mysqli_num_rows($types_result) > 0) {
                                    while ($type = mysqli_fetch_assoc($types_result)) {
                                        $selected = ($type_filter === $type['type']) ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($type['type']) . '" ' . $selected . '>' . ucfirst($type['type']) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="gear">Boîte de vitesses</label>
                            <select name="gear" id="gear">
                                <option value="">Toutes les boîtes</option>
                                <?php
                                if ($gears_result && mysqli_num_rows($gears_result) > 0) {
                                    while ($gear = mysqli_fetch_assoc($gears_result)) {
                                        $selected = ($car_gear_filter === $gear['gear']) ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($gear['gear']) . '" ' . $selected . '>' . ucfirst($gear['gear']) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="nb_places">Nombre de places</label>
                            <select name="nb_places" id="nb_places">
                                <option value="">Toutes</option>
                                <?php
                                if ($places_result && mysqli_num_rows($places_result) > 0) {
                                    while ($places = mysqli_fetch_assoc($places_result)) {
                                        $selected = ($nb_places_filter == $places['nb_places']) ? 'selected' : '';
                                        echo '<option value="' . $places['nb_places'] . '" ' . $selected . '>' . $places['nb_places'] . ' places</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="prix_min">Prix minimum (€/jour)</label>
                            <input type="number" name="prix_min" id="prix_min" min="0" step="10"
                                   value="<?php echo $car_prix_min > 0 ? $car_prix_min : ''; ?>" placeholder="0">
                        </div>

                        <div class="filter-group">
                            <label for="prix_max">Prix maximum (€/jour)</label>
                            <input type="number" name="prix_max" id="prix_max" min="0" step="10"
                                   value="<?php echo $car_prix_max > 0 ? $car_prix_max : ''; ?>" placeholder="1000">
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
                <p><i class="fas fa-car"></i> <?php echo $carCount; ?> voiture(s) trouvée(s)</p>
                <?php if (!empty($car_marque_filter) || !empty($car_type_filter) || !empty($car_gear_filter) || $car_prix_min > 0 || $car_prix_max > 0 || $car_nb_places_filter > 0): ?>
                    <p class="active-filters">
                        <i class="fas fa-filter"></i> Filtres actifs:
                        <?php
                        $active_filters = [];
                        if (!empty($car_marque_filter)) $active_filters[] = "Marque: $car_marque_filter";
                        if (!empty($car_type_filter)) $active_filters[] = "Type: $car_type_filter";
                        if (!empty($car_gear_filter)) $active_filters[] = "Boîte: $car_gear_filter";
                        if ($car_prix_min > 0) $active_filters[] = "Prix min: {$car_prix_min}€";
                        if ($car_prix_max > 0) $active_filters[] = "Prix max: {$car_prix_max}€";
                        if ($car_nb_places_filter > 0) $active_filters[] = "Places: $car_nb_places_filter";
                        echo implode(", ", $active_filters);
                        ?>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Cars Grid -->
            <div class="cars-grid">
                <?php
                echo "<!-- Starting car display loop -->";
                if ($car_result && mysqli_num_rows($car_result) > 0) {
                    echo "<!-- Result has rows, entering loop -->";
                    $carIndex = 0;
                    while ($car = mysqli_fetch_assoc($car_result)) {
                        $carIndex++;
                        echo "<!-- Processing car #$carIndex: " . ($car['marque'] ?? 'Unknown') . " -->";

                        $isBlocked = ($car['statut'] === 'réservé' || $car['statut'] === 'maintenance');
                        $blockClass = $isBlocked ? 'blocked' : '';
                        ?>
                        <div class="car-card <?php echo $blockClass; ?>"
                             data-marque="<?php echo htmlspecialchars($car['marque'] ?? ''); ?>"
                             data-type="<?php echo htmlspecialchars($car['type'] ?? ''); ?>"
                             data-statut="<?php echo htmlspecialchars($car['statut'] ?? ''); ?>"
                             data-prix="<?php echo (float)($car['prix_par_jour'] ?? 0); ?>">

                            <?php if ($isBlocked): ?>
                                <div class="blocked-overlay">
                                    <div class="blocked-content">
                                        <i class="fas fa-lock"></i>
                                        <span><?php echo ucfirst($car['statut'] ?? ''); ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="car-image">
                                <?php
                                $image = ($car['image'] && $car['image'] != '0') ? $car['image'] : 'https://images.pexels.com/photos/170811/pexels-photo-170811.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1';
                                ?>
                                <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars(($car['marque'] ?? '') . ' ' . ($car['modele'] ?? '')); ?>">
                                <div class="car-status <?php echo $car['statut'] ?? ''; ?>"><?php echo ucfirst($car['statut'] ?? ''); ?></div>
                            </div>

                            <div class="car-info">
                                <div class="car-title">
                                    <h3><?php echo htmlspecialchars(($car['marque'] ?? '') . ' ' . ($car['modele'] ?? '')); ?></h3>
                                    <p class="car-immatriculation"><?php echo htmlspecialchars($car['immatriculation'] ?? ''); ?></p>
                                </div>

                                <div class="car-features">
                                    <div class="feature">
                                        <i class="fas fa-gas-pump"></i>
                                        <span><?php echo ucfirst($car['type'] ?? ''); ?></span>
                                    </div>
                                    <div class="feature">
                                        <i class="fas fa-users"></i>
                                        <span><?php echo ($car['nb_places'] ?? 0); ?> places</span>
                                    </div>
                                    <div class="feature">
                                        <i class="fas fa-cogs"></i>
                                        <span><?php echo ucfirst($car['gear'] ?? 'Manuel'); ?></span>
                                    </div>
                                </div>

                                <div class="car-price">
                                    <div class="price-tag">
                                        <span class="price"><?php echo ($car['prix_par_jour'] ?? 0); ?> €</span>
                                        <span class="period">/ jour</span>
                                    </div>
                                    <?php if (!$isBlocked): ?>
                                        <a href="reservation.php?id=<?php echo $car['id_voiture'] ?? 0; ?>" class="btn btn-success">
                                            <i class="fas fa-calendar-check"></i> Réserver
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-disabled" disabled>
                                            <i class="fas fa-ban"></i> Indisponible
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php
                        echo "<!-- Car #$carIndex completed -->";
                    }
                    echo "<!-- Loop completed. Total cars processed: $carIndex -->";
                } else {
                    echo "<!-- No cars found or result is empty -->";
                    echo '<div class="no-results"><i class="fas fa-search"></i> Aucune voiture disponible avec ces critères.</div>';
                }
                echo "<!-- End of car display section -->";
                ?>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/main.js"></script>

</body>
</html>