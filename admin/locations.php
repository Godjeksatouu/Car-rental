<?php
session_start();
include '../includes/config.php';
include '../includes/functions.php';

// Check if user is admin
if (!isAdmin()) {
    header("Location: ../login.php");
    exit();
}

// Handle pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total
                FROM LOCATION l
                JOIN RESERVATION r ON l.id_reservation = r.id_reservation
                JOIN CLIENT c ON r.id_client = c.id_client
                JOIN VOITURE v ON r.id_voiture = v.id_voiture";

$count_result = mysqli_query($conn, $count_query);
$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $limit);

// Get locations with pagination
$query = "SELECT l.*, r.*, c.nom, c.prénom, c.email, c.téléphone,
                 v.marque, v.modele, v.immatriculation, v.prix_par_jour,
                 p.montant, p.date_paiement, p.mode_paiement
          FROM LOCATION l
          JOIN RESERVATION r ON l.id_reservation = r.id_reservation
          JOIN CLIENT c ON r.id_client = c.id_client
          JOIN VOITURE v ON r.id_voiture = v.id_voiture
          LEFT JOIN PAIEMENT p ON l.id_location = p.id_location
          ORDER BY r.date_debut DESC
          LIMIT $limit OFFSET $offset";

$result = mysqli_query($conn, $query);

$locations = [];
while ($row = mysqli_fetch_assoc($result)) {
    $locations[] = $row;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Locations - AutoDrive Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin-common.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/locations.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/admin-header.php'; ?>

    <section class="admin-section">
        <div class="container">
            <div class="section-header">
                <h1><i class="fas fa-key"></i> Gestion des Locations</h1>
                <p>Visualisez et gérez toutes les locations actives</p>
            </div>



            <!-- Statistics Summary -->
            <div class="stats-grid">
                <?php
                $stats_query = "SELECT 
                    COUNT(*) as total_locations,
                    SUM(CASE WHEN l.ETAT_PAIEMENT = 1 THEN 1 ELSE 0 END) as paid_locations,
                    SUM(CASE WHEN l.ETAT_PAIEMENT = 0 THEN 1 ELSE 0 END) as unpaid_locations,
                    SUM(CASE WHEN p.montant IS NOT NULL THEN p.montant ELSE 0 END) as total_revenue
                FROM LOCATION l 
                LEFT JOIN PAIEMENT p ON l.id_location = p.id_location";
                $stats_result = mysqli_query($conn, $stats_query);
                $stats = mysqli_fetch_assoc($stats_result);
                ?>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-key"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total_locations']; ?></h3>
                        <p>Total Locations</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['paid_locations']; ?></h3>
                        <p>Locations Payées</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['unpaid_locations']; ?></h3>
                        <p>Locations Non Payées</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-euro-sign"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['total_revenue'], 2); ?> €</h3>
                        <p>Revenus Total</p>
                    </div>
                </div>
            </div>

            <!-- Locations Table -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2>Liste des Locations (<?php echo $total_records; ?> résultats)</h2>
                </div>
                <div class="admin-card-body">
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID Location</th>
                                    <th>Client</th>
                                    <th>Voiture</th>
                                    <th>Période</th>
                                    <th>Prix/Jour</th>
                                    <th>Statut Paiement</th>
                                    <th>Montant</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($locations)): ?>
                                    <?php foreach ($locations as $location): ?>
                                        <?php
                                        $start_date = new DateTime($location['date_debut']);
                                        $end_date = new DateTime($location['date_fin']);
                                        $days = $start_date->diff($end_date)->days;
                                        $total_price = $days * $location['prix_par_jour'];
                                        ?>
                                        <tr>
                                            <td><strong>#<?php echo $location['id_location']; ?></strong></td>
                                            <td>
                                                <div class="client-info">
                                                    <strong><?php echo htmlspecialchars($location['nom'] . ' ' . $location['prénom']); ?></strong><br>
                                                    <small><?php echo htmlspecialchars($location['email']); ?></small><br>
                                                    <small><?php echo htmlspecialchars($location['téléphone']); ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="car-info">
                                                    <strong><?php echo htmlspecialchars($location['marque'] . ' ' . $location['modele']); ?></strong><br>
                                                    <small><?php echo htmlspecialchars($location['immatriculation']); ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="date-info">
                                                    <strong><?php echo formatDate($location['date_debut']); ?></strong><br>
                                                    <small>au</small><br>
                                                    <strong><?php echo formatDate($location['date_fin']); ?></strong><br>
                                                    <small>(<?php echo $days; ?> jours)</small>
                                                </div>
                                            </td>
                                            <td><strong><?php echo number_format($location['prix_par_jour'], 2); ?> €</strong></td>
                                            <td>
                                                <?php if ($location['ETAT_PAIEMENT']): ?>
                                                    <span class="status-badge paid">
                                                        <i class="fas fa-check-circle"></i> Payé
                                                    </span>
                                                    <?php if ($location['date_paiement']): ?>
                                                        <br><small>Le <?php echo formatDate($location['date_paiement']); ?></small>
                                                        <br><small><?php echo ucfirst($location['mode_paiement']); ?></small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="status-badge unpaid">
                                                        <i class="fas fa-exclamation-circle"></i> Non payé
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?php echo number_format($total_price, 2); ?> €</strong>
                                                <?php if ($location['montant'] && $location['montant'] != $total_price): ?>
                                                    <br><small>Payé: <?php echo number_format($location['montant'], 2); ?> €</small>
                                                <?php endif; ?>
                                            </td>
                                            <td class="actions">
                                                <a href="reservation-details.php?id=<?php echo $location['id_reservation']; ?>" 
                                                   class="btn btn-sm btn-primary" title="Voir détails">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit-reservation.php?id=<?php echo $location['id_reservation']; ?>" 
                                                   class="btn btn-sm btn-warning" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if (!$location['ETAT_PAIEMENT']): ?>
                                                    <a href="update-payment.php?id=<?php echo $location['id_location']; ?>" 
                                                       class="btn btn-sm btn-success" title="Marquer comme payé">
                                                        <i class="fas fa-money-bill-wave"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">
                                            <div class="empty-state">
                                                <i class="fas fa-key fa-3x"></i>
                                                <h3>Aucune location trouvée</h3>
                                                <p>Aucune location ne correspond à vos critères de recherche.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination-wrapper">
                    <nav class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>" class="pagination-btn">
                                <i class="fas fa-chevron-left"></i> Précédent
                            </a>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <a href="?page=<?php echo $i; ?>"
                               class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>" class="pagination-btn">
                                Suivant <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php include 'includes/admin-footer.php'; ?>
    <script src="../assets/js/main.js"></script>
</body>
</html>
