<header class="admin-header">
    <div class="container">
        <div class="admin-logo">
            <a href="dashboard.php">
                <i class="fas fa-tachometer-alt"></i> AutoDrive Admin
            </a>
        </div>
        <nav class="admin-nav">
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="cars.php"><i class="fas fa-car"></i> Voitures</a></li>
                <li><a href="clients.php"><i class="fas fa-users"></i> Clients</a></li>
                <li><a href="reservations.php"><i class="fas fa-calendar-alt"></i> Réservations</a></li>
                <li><a href="payments.php"><i class="fas fa-money-bill-wave"></i> Paiements</a></li>
            </ul>
        </nav>
        <div class="admin-user">
            <span class="user-name">
                <?php
                $adminId = $_SESSION['admin_id'];
                $query = "SELECT nom_utilisateur FROM ADMIN WHERE id_admin = $adminId";
                $result = mysqli_query($conn, $query);
                $admin = mysqli_fetch_assoc($result);
                echo $admin['nom_utilisateur'];
                ?>
            </span>
            <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </div>
</header>
<?php echo displayMessage(); ?>