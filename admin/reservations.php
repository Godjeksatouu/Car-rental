<?php
session_start();
include '../includes/config.php';
include '../includes/functions.php';

// Check if user is admin
if (!isAdmin()) {
    header("Location: ../login.php");
    exit();
}

// Get all reservations
$query = "SELECT r.*, c.nom, c.prénom, c.email, v.marque, v.modele, v.immatriculation, l.ETAT_PAIEMENT 
          FROM RESERVATION r 
          JOIN CLIENT c ON r.id_client = c.id_client 
          JOIN VOITURE v ON r.id_voiture = v.id_voiture 
          LEFT JOIN LOCATION l ON r.id_reservation = l.id_reservation 
          ORDER BY r.date_debut DESC";
$reservations = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Réservations - AutoDrive Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/admin-header.php'; ?>

    <section class="admin-dashboard">
        <div class="container">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2>Gestion des Réservations</h2>
                </div>
                <div class="admin-card-body">
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Client</th>
                                    <th>Voiture</th>
                                    <th>Dates</th>
                                    <th>Statut</th>
                                    <th>Paiement</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($reservations) > 0): ?>
                                    <?php while ($reservation = mysqli_fetch_assoc($reservations)): ?>
                                        <tr>
                                            <td>#<?php echo $reservation['id_reservation']; ?></td>
                                            <td>
                                                <?php echo $reservation['nom'] . ' ' . $reservation['prénom']; ?>
                                                <br>
                                                <small><?php echo $reservation['email']; ?></small>
                                            </td>
                                            <td><?php echo $reservation['marque'] . ' ' . $reservation['modele']; ?></td>
                                            <td><?php echo formatDate($reservation['date_debut']) . ' - ' . formatDate($reservation['date_fin']); ?></td>
                                            <td>
                                                <?php
                                                $today = date('Y-m-d');
                                                if ($reservation['date_fin'] < $today) {
                                                    echo '<span class="status-badge completed">Terminée</span>';
                                                } elseif ($reservation['date_debut'] > $today) {
                                                    echo '<span class="status-badge upcoming">À venir</span>';
                                                } else {
                                                    echo '<span class="status-badge active">En cours</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php if ($reservation['ETAT_PAIEMENT']): ?>
                                                    <span class="status-badge paid">Payé</span>
                                                <?php else: ?>
                                                    <span class="status-badge unpaid">Non payé</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="actions">
                                                <a href="reservation-details.php?id=<?php echo $reservation['id_reservation']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit-reservation.php?id=<?php echo $reservation['id_reservation']; ?>" class="btn btn-sm btn-warning">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">Aucune réservation trouvée</td>
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