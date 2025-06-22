<?php
session_start();
include 'includes/config.php';
include 'includes/functions.php';

// Basic error reporting
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
    <title>Nos Voitures</title>
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
    <h1>Nos Voitures</h1>
    
    <!-- Filters -->
    <form method="GET" action="cars.php">
        <div>
            <label>Marque:</label>
            <select name="marque">
                <option value="">Toutes</option>
                <?php while($m = mysqli_fetch_assoc($marques)): ?>
                    <option value="<?= $m['marque'] ?>" <?= ($marque == $m['marque']) ? 'selected' : '' ?>>
                        <?= $m['marque'] ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <div>
            <label>Type:</label>
            <select name="type">
                <option value="">Tous</option>
                <?php while($t = mysqli_fetch_assoc($types)): ?>
                    <option value="<?= $t['type'] ?>" <?= ($type == $t['type']) ? 'selected' : '' ?>>
                        <?= $t['type'] ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <div>
            <label>Boîte:</label>
            <select name="gear">
                <option value="">Toutes</option>
                <?php while($g = mysqli_fetch_assoc($gears)): ?>
                    <option value="<?= $g['gear'] ?>" <?= ($gear == $g['gear']) ? 'selected' : '' ?>>
                        <?= $g['gear'] ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <div>
            <label>Places:</label>
            <select name="nb_places">
                <option value="">Toutes</option>
                <?php while($p = mysqli_fetch_assoc($places_options)): ?>
                    <option value="<?= $p['nb_places'] ?>" <?= ($places == $p['nb_places']) ? 'selected' : '' ?>>
                        <?= $p['nb_places'] ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <div>
            <label>Prix min:</label>
            <input type="number" name="prix_min" value="<?= $prix_min ?>">
        </div>
        
        <div>
            <label>Prix max:</label>
            <input type="number" name="prix_max" value="<?= $prix_max ?>">
        </div>
        
        <button type="submit">Filtrer</button>
        <a href="cars.php">Reset</a>
    </form>
    
    <p><?= $carCount ?> voitures trouvées</p>
    
    <!-- Cars list -->
    <div class="cars">
        <?php if ($carCount > 0): ?>
            <?php while($car = mysqli_fetch_assoc($result)): ?>
                <div class="car">
                    <img src="<?= $car['image'] ?>" alt="<?= $car['marque'] ?>">
                    <h3><?= $car['marque'] ?> <?= $car['modele'] ?></h3>
                    <p>Prix: <?= $car['prix_par_jour'] ?>€/jour</p>
                    <p>Places: <?= $car['nb_places'] ?></p>
                    <p>Boîte: <?= $car['gear'] ?></p>
                    <p>Statut: <?= $car['statut'] ?></p>
                    <a href="reservation.php?id=<?= $car['id_voiture'] ?>">Réserver</a>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>Aucune voiture trouvée</p>
        <?php endif; ?>
    </div>
     <script src="assets/js/main.js"></script>
    <?php include 'includes/footer.php'; ?>
</body>
</html>