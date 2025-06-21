<header>
    <div class="container">
        <div class="logo">
            <a href="index.php">
                <h1><i class="fas fa-car"></i> AutoDrive</h1>
            </a>
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Accueil</a></li>
                <li><a href="cars.php">Voitures</a></li>
                <li><a href="services.php">Services</a></li>
                <li><a href="about.php">À Propos</a></li>
            </ul>
        </nav>
        <div class="user-actions">
            <?php if (isLoggedIn()): ?>
                <div class="dropdown">
                    <button class="dropdown-btn">
                        <i class="fas fa-user-circle"></i>
                        <?php
                        // Get current user's name safely using prepared statement
                        $current_user_id = $_SESSION['user_id'];
                        $user_query = "SELECT nom, prénom FROM CLIENT WHERE id_client = ?";
                        $user_statement = mysqli_prepare($conn, $user_query);
                        mysqli_stmt_bind_param($user_statement, "i", $current_user_id);
                        mysqli_stmt_execute($user_statement);
                        $user_result = mysqli_stmt_get_result($user_statement);
                        $user_data = mysqli_fetch_assoc($user_result);

                        // Display first name if available, otherwise last name
                        $display_name = !empty($user_data['prénom']) ? $user_data['prénom'] : $user_data['nom'];
                        echo htmlspecialchars($display_name);
                        ?>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="dropdown-content">
                        <a href="profile.php"><i class="fas fa-user"></i> Mon Profil</a>
                        <a href="profile.php#reservations"><i class="fas fa-calendar-alt"></i> Mes Réservations</a>
                        <?php if (isAdmin()): ?>
                            <a href="admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                        <?php endif; ?>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php" class="btn btn-outline">Connexion</a>
                <a href="register.php" class="btn btn-primary">Inscription</a>
            <?php endif; ?>
        </div>
        <div class="mobile-menu-btn">
            <i class="fas fa-bars"></i>
        </div>
    </div>
</header>
<div class="mobile-menu">
    <ul>
        <li><a href="index.php">Accueil</a></li>
        <li><a href="cars.php">Voitures</a></li>
        <li><a href="services.php">Services</a></li>
        <li><a href="about.php">À Propos</a></li>
        <?php if (isLoggedIn()): ?>
            <li><a href="profile.php">Mon Profil</a></li>
            <li><a href="profile.php#reservations">Mes Réservations</a></li>
            <?php if (isAdmin()): ?>
                <li><a href="admin/dashboard.php">Dashboard</a></li>
            <?php endif; ?>
            <li><a href="logout.php">Déconnexion</a></li>
        <?php else: ?>
            <li><a href="login.php">Connexion</a></li>
            <li><a href="register.php">Inscription</a></li>
        <?php endif; ?>
    </ul>
</div>
<?php echo displayMessage(); ?>