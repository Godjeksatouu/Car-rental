<?php
session_start();
include '../includes/config.php';
include '../includes/functions.php';

// Check if user is admin
if (!isAdmin()) {
    header("Location: ../login.php");
    exit();
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$payment_filter = isset($_GET['payment']) ? $_GET['payment'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build WHERE conditions
$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($search)) {
    $where_conditions[] = "(c.nom LIKE ? OR c.prénom LIKE ? OR c.email LIKE ? OR v.marque LIKE ? OR v.modele LIKE ? OR v.immatriculation LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param, $search_param]);
    $param_types .= 'ssssss';
}

if (!empty($date_from)) {
    $where_conditions[] = "r.date_debut >= ?";
    $params[] = $date_from;
    $param_types .= 's';
}

if (!empty($date_to)) {
    $where_conditions[] = "r.date_fin <= ?";
    $params[] = $date_to;
    $param_types .= 's';
}

if (!empty($payment_filter)) {
    if ($payment_filter === 'paid') {
        $where_conditions[] = "l.ETAT_PAIEMENT = 1";
    } elseif ($payment_filter === 'unpaid') {
        $where_conditions[] = "(l.ETAT_PAIEMENT = 0 OR l.ETAT_PAIEMENT IS NULL)";
    }
}

// Build the complete query
$base_query = "SELECT r.*, c.nom, c.prénom, c.email, c.téléphone, v.marque, v.modele, v.immatriculation, v.prix_par_jour, l.ETAT_PAIEMENT
               FROM RESERVATION r
               JOIN CLIENT c ON r.id_client = c.id_client
               JOIN VOITURE v ON r.id_voiture = v.id_voiture
               LEFT JOIN LOCATION l ON r.id_reservation = l.id_reservation";

if (!empty($where_conditions)) {
    $base_query .= " WHERE " . implode(" AND ", $where_conditions);
}

$base_query .= " ORDER BY r.date_debut DESC";

// Execute query
if (!empty($params)) {
    $stmt = mysqli_prepare($conn, $base_query);
    mysqli_stmt_bind_param($stmt, $param_types, ...$params);
    mysqli_stmt_execute($stmt);
    $reservations = mysqli_stmt_get_result($stmt);
} else {
    $reservations = mysqli_query($conn, $base_query);
}

// Get statistics
$stats_query = "SELECT
    COUNT(*) as total_reservations,
    COUNT(CASE WHEN l.ETAT_PAIEMENT = 1 THEN 1 END) as paid_reservations,
    COUNT(CASE WHEN l.ETAT_PAIEMENT = 0 OR l.ETAT_PAIEMENT IS NULL THEN 1 END) as unpaid_reservations,
    COUNT(CASE WHEN r.date_debut > CURDATE() THEN 1 END) as upcoming_reservations,
    COUNT(CASE WHEN r.date_debut <= CURDATE() AND r.date_fin >= CURDATE() THEN 1 END) as active_reservations,
    COUNT(CASE WHEN r.date_fin < CURDATE() THEN 1 END) as completed_reservations,
    SUM(CASE WHEN l.ETAT_PAIEMENT = 1 THEN DATEDIFF(r.date_fin, r.date_debut) * v.prix_par_jour ELSE 0 END) as total_revenue
    FROM RESERVATION r
    JOIN CLIENT c ON r.id_client = c.id_client
    JOIN VOITURE v ON r.id_voiture = v.id_voiture
    LEFT JOIN LOCATION l ON r.id_reservation = l.id_reservation";

$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
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
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['total_reservations']); ?></h3>
                        <p>Total Réservations</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon active">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['active_reservations']); ?></h3>
                        <p>En Cours</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon upcoming">
                        <i class="fas fa-calendar-plus"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['upcoming_reservations']); ?></h3>
                        <p>À Venir</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon revenue">
                        <i class="fas fa-euro-sign"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['total_revenue'], 2); ?>€</h3>
                        <p>Revenus Totaux</p>
                    </div>
                </div>
            </div>

            <!-- Filters and Search -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2><i class="fas fa-filter"></i> Filtres et Recherche</h2>
                    <button type="button" class="btn btn-outline" onclick="toggleFilters()">
                        <i class="fas fa-chevron-down" id="filter-toggle-icon"></i>
                    </button>
                </div>
                <div class="admin-card-body" id="filters-section" style="display: none;">
                    <form method="GET" action="" class="filters-form">
                        <div class="filter-row">
                            <div class="filter-group">
                                <label for="search">Recherche</label>
                                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>"
                                       placeholder="Nom, email, voiture...">
                            </div>
                            <div class="filter-group">
                                <label for="payment">Statut Paiement</label>
                                <select id="payment" name="payment">
                                    <option value="">Tous</option>
                                    <option value="paid" <?php echo $payment_filter === 'paid' ? 'selected' : ''; ?>>Payé</option>
                                    <option value="unpaid" <?php echo $payment_filter === 'unpaid' ? 'selected' : ''; ?>>Non payé</option>
                                </select>
                            </div>
                        </div>
                        <div class="filter-row">
                            <div class="filter-group">
                                <label for="date_from">Date de début</label>
                                <input type="date" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                            </div>
                            <div class="filter-group">
                                <label for="date_to">Date de fin</label>
                                <input type="date" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                            </div>
                        </div>
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Filtrer
                            </button>
                            <a href="reservations.php" class="btn btn-outline">
                                <i class="fas fa-undo"></i> Réinitialiser
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Reservations Table -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2><i class="fas fa-calendar-alt"></i> Gestion des Réservations</h2>
                    <div class="header-actions">
                        <span class="results-count"><?php echo mysqli_num_rows($reservations); ?> résultat(s)</span>
                        <button class="btn btn-outline" onclick="exportReservations()">
                            <i class="fas fa-download"></i> Exporter
                        </button>
                    </div>
                </div>
                <div class="admin-card-body">
                    <div class="table-responsive">
                        <table class="admin-table enhanced-table">
                            <thead>
                                <tr>
                                    <th>
                                        <input type="checkbox" id="select-all" onchange="toggleAllCheckboxes()">
                                    </th>
                                    <th>ID</th>
                                    <th>Client</th>
                                    <th>Voiture</th>
                                    <th>Dates</th>
                                    <th>Durée</th>
                                    <th>Prix Total</th>
                                    <th>Statut</th>
                                    <th>Paiement</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($reservations) > 0): ?>
                                    <?php while ($reservation = mysqli_fetch_assoc($reservations)): ?>
                                        <?php
                                        $today = date('Y-m-d');
                                        $start_date = new DateTime($reservation['date_debut']);
                                        $end_date = new DateTime($reservation['date_fin']);
                                        $duration = $start_date->diff($end_date)->days + 1;
                                        $total_price = $duration * $reservation['prix_par_jour'];

                                        // Determine row class based on status
                                        $row_class = '';
                                        if ($reservation['date_fin'] < $today) {
                                            $row_class = 'completed-row';
                                        } elseif ($reservation['date_debut'] <= $today && $reservation['date_fin'] >= $today) {
                                            $row_class = 'active-row';
                                        } else {
                                            $row_class = 'upcoming-row';
                                        }
                                        ?>
                                        <tr class="<?php echo $row_class; ?>">
                                            <td>
                                                <input type="checkbox" class="row-checkbox" value="<?php echo $reservation['id_reservation']; ?>">
                                            </td>
                                            <td>
                                                <span class="reservation-id">#<?php echo $reservation['id_reservation']; ?></span>
                                            </td>
                                            <td class="client-info">
                                                <div class="client-name">
                                                    <strong><?php echo htmlspecialchars($reservation['nom'] . ' ' . $reservation['prénom']); ?></strong>
                                                </div>
                                                <div class="client-contact">
                                                    <small><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($reservation['email']); ?></small>
                                                    <?php if (!empty($reservation['téléphone'])): ?>
                                                        <small><i class="fas fa-phone"></i> <?php echo htmlspecialchars($reservation['téléphone']); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="car-info">
                                                <div class="car-name">
                                                    <strong><?php echo htmlspecialchars($reservation['marque'] . ' ' . $reservation['modele']); ?></strong>
                                                </div>
                                                <small class="car-plate"><?php echo htmlspecialchars($reservation['immatriculation']); ?></small>
                                            </td>
                                            <td class="date-info">
                                                <div class="date-range">
                                                    <div class="date-start">
                                                        <i class="fas fa-calendar-plus"></i>
                                                        <?php echo date('d/m/Y', strtotime($reservation['date_debut'])); ?>
                                                    </div>
                                                    <div class="date-end">
                                                        <i class="fas fa-calendar-minus"></i>
                                                        <?php echo date('d/m/Y', strtotime($reservation['date_fin'])); ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="duration">
                                                <span class="duration-badge"><?php echo $duration; ?> jour<?php echo $duration > 1 ? 's' : ''; ?></span>
                                            </td>
                                            <td class="price">
                                                <span class="price-amount"><?php echo number_format($total_price, 2); ?>€</span>
                                                <small class="price-per-day"><?php echo number_format($reservation['prix_par_jour'], 2); ?>€/jour</small>
                                            </td>
                                            <td>
                                                <?php
                                                if ($reservation['date_fin'] < $today) {
                                                    echo '<span class="status-badge completed"><i class="fas fa-check-circle"></i> Terminée</span>';
                                                } elseif ($reservation['date_debut'] > $today) {
                                                    echo '<span class="status-badge upcoming"><i class="fas fa-clock"></i> À venir</span>';
                                                } else {
                                                    echo '<span class="status-badge active"><i class="fas fa-play-circle"></i> En cours</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php if ($reservation['ETAT_PAIEMENT']): ?>
                                                    <span class="status-badge paid"><i class="fas fa-check-circle"></i> Payé</span>
                                                <?php else: ?>
                                                    <span class="status-badge unpaid"><i class="fas fa-exclamation-circle"></i> Non payé</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="actions">
                                                <div class="action-buttons">
                                                    <a href="reservation-details.php?id=<?php echo $reservation['id_reservation']; ?>"
                                                       class="btn btn-sm btn-primary" title="Voir détails">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="edit-reservation.php?id=<?php echo $reservation['id_reservation']; ?>"
                                                       class="btn btn-sm btn-warning" title="Modifier">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php if (!$reservation['ETAT_PAIEMENT']): ?>
                                                        <button onclick="markAsPaid(<?php echo $reservation['id_reservation']; ?>)"
                                                                class="btn btn-sm btn-success" title="Marquer comme payé">
                                                            <i class="fas fa-money-bill-wave"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <button onclick="deleteReservation(<?php echo $reservation['id_reservation']; ?>)"
                                                            class="btn btn-sm btn-danger" title="Supprimer">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="10" class="text-center empty-state">
                                            <div class="empty-state-content">
                                                <i class="fas fa-calendar-times"></i>
                                                <h3>Aucune réservation trouvée</h3>
                                                <p>Aucune réservation ne correspond aux critères de recherche.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Bulk Actions -->
                    <?php if (mysqli_num_rows($reservations) > 0): ?>
                        <div class="bulk-actions" id="bulk-actions" style="display: none;">
                            <div class="bulk-actions-content">
                                <span class="selected-count">0 réservation(s) sélectionnée(s)</span>
                                <div class="bulk-buttons">
                                    <button class="btn btn-success" onclick="bulkMarkAsPaid()">
                                        <i class="fas fa-money-bill-wave"></i> Marquer comme payé
                                    </button>
                                    <button class="btn btn-danger" onclick="bulkDelete()">
                                        <i class="fas fa-trash"></i> Supprimer
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/admin-footer.php'; ?>
    <script src="../assets/js/main.js"></script>
    <script>
        // Toggle filters section
        function toggleFilters() {
            const filtersSection = document.getElementById('filters-section');
            const toggleIcon = document.getElementById('filter-toggle-icon');

            if (filtersSection.style.display === 'none') {
                filtersSection.style.display = 'block';
                toggleIcon.classList.remove('fa-chevron-down');
                toggleIcon.classList.add('fa-chevron-up');
            } else {
                filtersSection.style.display = 'none';
                toggleIcon.classList.remove('fa-chevron-up');
                toggleIcon.classList.add('fa-chevron-down');
            }
        }

        // Checkbox functionality
        function toggleAllCheckboxes() {
            const selectAll = document.getElementById('select-all');
            const checkboxes = document.querySelectorAll('.row-checkbox');

            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });

            updateBulkActions();
        }

        function updateBulkActions() {
            const checkboxes = document.querySelectorAll('.row-checkbox:checked');
            const bulkActions = document.getElementById('bulk-actions');
            const selectedCount = document.querySelector('.selected-count');

            if (checkboxes.length > 0) {
                bulkActions.style.display = 'block';
                selectedCount.textContent = `${checkboxes.length} réservation(s) sélectionnée(s)`;
            } else {
                bulkActions.style.display = 'none';
            }
        }

        // Add event listeners to checkboxes
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateBulkActions);
            });
        });

        // Mark as paid functionality
        function markAsPaid(reservationId) {
            if (confirm('Marquer cette réservation comme payée ?')) {
                // Here you would make an AJAX call to update the payment status
                window.location.href = `update-payment.php?id=${reservationId}&action=mark_paid`;
            }
        }

        // Delete reservation
        function deleteReservation(reservationId) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cette réservation ?')) {
                window.location.href = `delete-reservation.php?id=${reservationId}`;
            }
        }

        // Bulk actions
        function bulkMarkAsPaid() {
            const checkboxes = document.querySelectorAll('.row-checkbox:checked');
            const ids = Array.from(checkboxes).map(cb => cb.value);

            if (ids.length === 0) {
                alert('Veuillez sélectionner au moins une réservation.');
                return;
            }

            if (confirm(`Marquer ${ids.length} réservation(s) comme payée(s) ?`)) {
                window.location.href = `bulk-actions.php?action=mark_paid&ids=${ids.join(',')}`;
            }
        }

        function bulkDelete() {
            const checkboxes = document.querySelectorAll('.row-checkbox:checked');
            const ids = Array.from(checkboxes).map(cb => cb.value);

            if (ids.length === 0) {
                alert('Veuillez sélectionner au moins une réservation.');
                return;
            }

            if (confirm(`Êtes-vous sûr de vouloir supprimer ${ids.length} réservation(s) ?`)) {
                window.location.href = `bulk-actions.php?action=delete&ids=${ids.join(',')}`;
            }
        }

        // Export functionality
        function exportReservations() {
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('export', 'csv');
            window.location.href = currentUrl.toString();
        }

        // Auto-refresh every 30 seconds
        setInterval(function() {
            // Only refresh if no filters are active to avoid losing user input
            const urlParams = new URLSearchParams(window.location.search);
            if (!urlParams.has('search') && !urlParams.has('status') && !urlParams.has('payment')) {
                location.reload();
            }
        }, 30000);
    </script>
</body>
</html>