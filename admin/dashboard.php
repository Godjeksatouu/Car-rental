<?php
session_start();
include '../includes/config.php';
include '../includes/functions.php';

// Check if user is admin
if (!isAdmin()) {
    header("Location: ../login.php");
    exit();
}

// Get counts for dashboard stats
$stats = [
    'voitures' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM VOITURE"))['count'],
    'clients' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM CLIENT"))['count'],
    'reservations' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM RESERVATION"))['count'],
    'locations' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM LOCATION"))['count']
];

// Get recent reservations
$query = "SELECT r.*, c.nom, c.prénom, c.email, v.marque, v.modele, v.immatriculation, l.ETAT_PAIEMENT 
          FROM RESERVATION r 
          JOIN CLIENT c ON r.id_client = c.id_client 
          JOIN VOITURE v ON r.id_voiture = v.id_voiture 
          LEFT JOIN LOCATION l ON r.id_reservation = l.id_reservation 
          ORDER BY r.date_debut DESC 
          LIMIT 5";
$recentReservations = mysqli_query($conn, $query);

// Get cars status
$query = "SELECT statut, COUNT(*) as count FROM VOITURE GROUP BY statut";
$carsStatus = mysqli_query($conn, $query);
$carStats = [];
while ($row = mysqli_fetch_assoc($carsStatus)) {
    $carStats[$row['statut']] = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - AutoDrive</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/admin-header.php'; ?>

    <section class="admin-dashboard">
        <div class="container">
            <div class="section-header">
                <h1>Tableau de bord</h1>
                <p>Bienvenue dans l'interface d'administration</p>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-car"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['voitures']; ?></h3>
                        <p>Voitures</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['clients']; ?></h3>
                        <p>Clients</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['reservations']; ?></h3>
                        <p>Réservations</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-key"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['locations']; ?></h3>
                        <p>Locations</p>
                    </div>
                </div>
            </div>
            
            <div class="admin-content">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2>Réservations récentes</h2>
                        <a href="reservations.php" class="btn btn-primary">Voir toutes</a>
                    </div>
                    <div class="admin-card-body">
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Référence</th>
                                        <th>Client</th>
                                        <th>Voiture</th>
                                        <th>Dates</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($recentReservations) > 0): ?>
                                        <?php while ($reservation = mysqli_fetch_assoc($recentReservations)): ?>
                                            <tr>
                                                <td>#<?php echo $reservation['id_reservation']; ?></td>
                                                <td><?php echo $reservation['nom'] . ' ' . $reservation['prénom']; ?></td>
                                                <td><?php echo $reservation['marque'] . ' ' . $reservation['modele']; ?></td>
                                                <td><?php echo formatDate($reservation['date_debut']) . ' - ' . formatDate($reservation['date_fin']); ?></td>
                                                <td>
                                                    <?php if ($reservation['ETAT_PAIEMENT']): ?>
                                                        <span class="status-badge paid">Payé</span>
                                                    <?php else: ?>
                                                        <span class="status-badge unpaid">Non payé</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="actions">
                                                    <a href="reservation-details.php?id=<?php echo $reservation['id_reservation']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-eye"></i></a>
                                                    <a href="edit-reservation.php?id=<?php echo $reservation['id_reservation']; ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">Aucune réservation récente</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="admin-card">
                            <div class="admin-card-header">
                                <h2>État des voitures</h2>
                                <a href="cars.php" class="btn btn-primary">Gérer les voitures</a>
                            </div>
                            <div class="admin-card-body">
                                <div class="status-chart">
                                    <div class="status-bar">
                                        <div class="status-segment disponible" style="width: <?php echo ($carStats['disponible'] ?? 0) / $stats['voitures'] * 100; ?>%">
                                            <?php echo $carStats['disponible'] ?? 0; ?> Disponibles
                                        </div>
                                        <div class="status-segment réservé" style="width: <?php echo ($carStats['réservé'] ?? 0) / $stats['voitures'] * 100; ?>%">
                                            <?php echo $carStats['réservé'] ?? 0; ?> Réservées
                                        </div>
                                        <div class="status-segment en-location" style="width: <?php echo ($carStats['en location'] ?? 0) / $stats['voitures'] * 100; ?>%">
                                            <?php echo $carStats['en location'] ?? 0; ?> En location
                                        </div>
                                        <div class="status-segment maintenance" style="width: <?php echo ($carStats['maintenance'] ?? 0) / $stats['voitures'] * 100; ?>%">
                                            <?php echo $carStats['maintenance'] ?? 0; ?> En maintenance
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="quick-actions">
                                    <h3>Actions rapides</h3>
                                    <div class="action-buttons">
                                        <a href="add-car.php" class="btn btn-primary"><i class="fas fa-plus"></i> Ajouter une voiture</a>
                                        <a href="maintenance.php" class="btn btn-warning"><i class="fas fa-tools"></i> Maintenance</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="admin-card">
                            <div class="admin-card-header">
                                <h2>Paiements récents</h2>
                                <a href="payments.php" class="btn btn-primary">Voir tous</a>
                            </div>
                            <div class="admin-card-body">
                                <?php
                                $query = "SELECT p.*, c.nom, c.prénom 
                                          FROM PAIEMENT p 
                                          JOIN LOCATION l ON p.id_location = l.id_location 
                                          JOIN RESERVATION r ON l.id_reservation = r.id_reservation 
                                          JOIN CLIENT c ON r.id_client = c.id_client 
                                          ORDER BY p.date_paiement DESC 
                                          LIMIT 5";
                                $recentPayments = mysqli_query($conn, $query);
                                ?>
                                
                                <div class="table-responsive">
                                    <table class="admin-table">
                                        <thead>
                                            <tr>
                                                <th>Référence</th>
                                                <th>Client</th>
                                                <th>Date</th>
                                                <th>Montant</th>
                                                <th>Mode</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (mysqli_num_rows($recentPayments) > 0): ?>
                                                <?php while ($payment = mysqli_fetch_assoc($recentPayments)): ?>
                                                    <tr>
                                                        <td>#<?php echo $payment['id_paiement']; ?></td>
                                                        <td><?php echo $payment['nom'] . ' ' . $payment['prénom']; ?></td>
                                                        <td><?php echo formatDate($payment['date_paiement']); ?></td>
                                                        <td><?php echo $payment['montant']; ?> €</td>
                                                        <td><?php echo ucfirst($payment['mode_paiement']); ?></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="5" class="text-center">Aucun paiement récent</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/admin-footer.php'; ?>
    <script src="../assets/js/main.js"></script>
</body>
</html>