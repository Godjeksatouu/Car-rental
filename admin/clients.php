<?php
session_start();
include '../includes/config.php';
include '../includes/functions.php';

// Check if user is admin
if (!isAdmin()) {
    header("Location: ../login.php");
    exit();
}

// Get all clients
$query = "SELECT c.*, 
          COUNT(DISTINCT r.id_reservation) as total_reservations,
          COUNT(DISTINCT CASE WHEN l.ETAT_PAIEMENT = 1 THEN l.id_location END) as locations_payees
          FROM CLIENT c
          LEFT JOIN RESERVATION r ON c.id_client = r.id_client
          LEFT JOIN LOCATION l ON r.id_reservation = l.id_reservation
          GROUP BY c.id_client
          ORDER BY c.id_client DESC";
$clients = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Clients - AutoDrive Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/admin-header.php'; ?>

    <section class="admin-dashboard">
        <div class="container">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2>Gestion des Clients</h2>
                </div>
                <div class="admin-card-body">
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nom</th>
                                    <th>Email</th>
                                    <th>Téléphone</th>
                                    <th>Réservations</th>
                                    <th>Locations payées</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($clients) > 0): ?>
                                    <?php while ($client = mysqli_fetch_assoc($clients)): ?>
                                        <tr>
                                            <td>#<?php echo $client['id_client']; ?></td>
                                            <td><?php echo $client['nom'] . ' ' . $client['prénom']; ?></td>
                                            <td><?php echo $client['email']; ?></td>
                                            <td><?php echo $client['téléphone']; ?></td>
                                            <td><?php echo $client['total_reservations']; ?></td>
                                            <td><?php echo $client['locations_payees']; ?></td>
                                            <td class="actions">
                                                <a href="client-details.php?id=<?php echo $client['id_client']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">Aucun client trouvé</td>
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