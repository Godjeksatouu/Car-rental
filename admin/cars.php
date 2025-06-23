<?php
session_start();
include '../includes/config.php';
include '../includes/functions.php';

// Check if user is admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Handle car deletion
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $carId = (int)$_GET['delete'];
    
    // Check if car is in use in reservations
    $checkQuery = "SELECT COUNT(*) as count FROM reservation WHERE id_voiture = ?";
    $stmt = mysqli_prepare($conn, $checkQuery);
    mysqli_stmt_bind_param($stmt, "i", $carId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    if ($row['count'] > 0) {
        // Car is in use, cannot delete
        redirectWithMessage('cars.php', 'Impossible de supprimer cette voiture car elle est utilisée dans des réservations', 'error');
    } else {
        // Car is not in use, proceed with deletion
        $query = "DELETE FROM voiture WHERE id_voiture = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $carId);
        
        if (mysqli_stmt_execute($stmt)) {
            redirectWithMessage('cars.php', 'Voiture supprimée avec succès', 'success');
        } else {
            redirectWithMessage('cars.php', 'Erreur lors de la suppression: ' . mysqli_error($conn), 'error');
        }
    }
}

// Get all cars
$query = "SELECT * FROM voiture ORDER BY id_voiture DESC";
$cars = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Voitures - AutoDrive Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin-common.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-body {
            background-color: var(--gray-100);
            min-height: 100vh;
        }
        
        .admin-dashboard {
            padding: 30px 0;
        }
        
        .admin-card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            margin-bottom: 30px;
        }
        
        .admin-card-header {
            padding: 20px;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-card-header h2 {
            margin: 0;
            color: var(--gray-800);
        }
        
        .admin-card-body {
            padding: 20px;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .admin-table th {
            text-align: left;
            padding: 12px 15px;
            border-bottom: 1px solid var(--gray-300);
            color: var(--gray-700);
            font-weight: 600;
        }
        
        .admin-table td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--gray-200);
            color: var(--gray-800);
        }
        
        .admin-table tr:last-child td {
            border-bottom: none;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .disponible {
            background-color: rgba(72, 187, 120, 0.1);
            color: var(--success);
        }
        
        .réservé {
            background-color: rgba(236, 201, 75, 0.1);
            color: var(--warning);
        }
        
        .en-location {
            background-color: rgba(66, 153, 225, 0.1);
            color: var(--primary-color);
        }
        
        .maintenance {
            background-color: rgba(245, 101, 101, 0.1);
            color: var(--error);
        }
        
        .actions {
            display: flex;
            gap: 5px;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: var(--border-radius);
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
        }
        
        .btn-warning {
            background-color: var(--warning);
            color: white;
        }
        
        .btn-warning:hover {
            background-color: #d69e2e;
        }
        
        .btn-danger {
            background-color: var(--error);
            color: white;
        }
        
        .btn-danger:hover {
            background-color: var(--error-dark);
        }
    </style>
</head>
<body class="admin-body">
    <?php include 'includes/admin-header.php'; ?>

    <section class="admin-dashboard">
        <div class="container">
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                    <?php 
                        echo $_SESSION['message']; 
                        unset($_SESSION['message']);
                        unset($_SESSION['message_type']);
                    ?>
                </div>
            <?php endif; ?>
            
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2>Gestion des Voitures</h2>
                    <a href="add-cars.php" class="btn btn-primary">Ajouter une voiture</a>
                </div>
                <div class="admin-card-body">
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Image</th>
                                    <th>Marque</th>
                                    <th>Modèle</th>
                                    <th>Immatriculation</th>
                                    <th>Type</th>
                                    <th>Places</th>
                                    <th>Prix/Jour</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($cars) > 0): ?>
                                    <?php while ($car = mysqli_fetch_assoc($cars)): ?>
                                        <tr>
                                            <td>#<?php echo $car['id_voiture']; ?></td>
                                            <td>
                                                <?php if ($car['image'] && $car['image'] != '0'): ?>
                                                    <img src="<?php echo $car['image']; ?>" alt="<?php echo $car['marque'] . ' ' . $car['modele']; ?>" style="width: 50px; height: 50px; object-fit: cover;">
                                                <?php else: ?>
                                                    <i class="fas fa-car"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $car['marque']; ?></td>
                                            <td><?php echo $car['modele']; ?></td>
                                            <td><?php echo $car['immatriculation']; ?></td>
                                            <td><?php echo ucfirst($car['type']); ?></td>
                                            <td><?php echo $car['nb_places']; ?></td>
                                            <td><?php echo $car['prix_par_jour']; ?> €</td>
                                            <td>
                                                <span class="status-badge <?php echo str_replace(' ', '-', $car['statut']); ?>">
                                                    <?php echo ucfirst($car['statut']); ?>
                                                </span>
                                            </td>
                                            <td class="actions">
                                                <a href="edit-car.php?id=<?php echo $car['id_voiture']; ?>" class="btn btn-sm btn-warning">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="cars.php?delete=<?php echo $car['id_voiture']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette voiture ?');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="10" class="text-center">Aucune voiture trouvée</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/admin-footer.php'; ?>
    <script src="../assets/js/main.js"></script>
</body>
</html>
