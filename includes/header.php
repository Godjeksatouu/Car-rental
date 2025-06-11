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
                        $userId = $_SESSION['user_id'];
                        $query = "SELECT nom, prénom FROM CLIENT WHERE id_client = $userId";
                        $result = mysqli_query($conn, $query);
                        $user = mysqli_fetch_assoc($result);
                        echo $user['prénom'] ? $user['prénom'] : $user['nom'];
                        ?>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="dropdown-content">
                        <a href="profile.php"><i class="fas fa-user"></i> Mon Profil</a>
                        <a href="reservations.php"><i class="fas fa-calendar-alt"></i> Mes Réservations</a>
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
            <li><a href="reservations.php">Mes Réservations</a></li>
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