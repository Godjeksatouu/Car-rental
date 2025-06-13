<?php
session_start();
include '../includes/config.php';
include '../includes/functions.php';

// Check if user is admin
if (!isAdmin()) {
    header("Location: ../login.php");
    exit();
}

// Handle car deletion
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $carId = (int)$_GET['delete'];
    $query = "DELETE FROM VOITURE WHERE id_voiture = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $carId);
    
    if (mysqli_stmt_execute($stmt)) {
        redirectWithMessage('cars.php', 'Voiture supprimée avec succès', 'success');
    } else {
        redirectWithMessage('cars.php', 'Erreur lors de la suppression', 'error');
    }
}

// Get all cars
$query = "SELECT * FROM VOITURE ORDER BY id_voiture DESC";
$cars = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Voitures - AutoDrive Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/admin-header.php'; ?>

    <section class="admin-dashboard">
        <div class="container">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2>Gestion des Voitures</h2>
                    <a href="add-car.php" class="btn btn-primary">Ajouter une voiture</a>
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
                                                <?php if ($car['image']): ?>
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
                                                <span class="status-badge <?php echo $car['statut']; ?>">
                                                    <?php echo ucfirst($car['statut']); ?>
                                                </span>
                                            </td>
                                            <td class="actions">
                                                <a href="edit-car.php?id=<?php echo $car['id_voiture']; ?>" class="btn btn-sm btn-warning">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="cars.php?delete=<?php echo $car['id_voiture']; ?>" class="btn btn-sm btn-error" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette voiture ?')">
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