<?php
session_start();
include 'includes/config.php';
include 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirectWithMessage('login.php', 'Veuillez vous connecter pour accéder à votre profil', 'error');
}

$userId = $_SESSION['user_id'];

// Get user details
$query = "SELECT * FROM CLIENT WHERE id_client = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

$errors = [];
$success = "";

// Process profile update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom = sanitize($_POST["nom"]);
    $prenom = sanitize($_POST["prenom"]);
    $email = sanitize($_POST["email"]);
    $telephone = sanitize($_POST["telephone"]);
    
    // Validate inputs
    if (empty($nom)) {
        $errors[] = "Nom est requis";
    }
    
    if (empty($email)) {
        $errors[] = "Email est requis";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format d'email invalide";
    }
    
    if (empty($telephone)) {
        $errors[] = "Téléphone est requis";
    }
    
    // Check if email already exists (except for current user)
    if (empty($errors)) {
        $query = "SELECT id_client FROM CLIENT WHERE email = ? AND id_client != ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "si", $email, $userId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = "Cet email est déjà utilisé";
        }
    }
    
    // Check if phone already exists (except for current user)
    if (empty($errors)) {
        $query = "SELECT id_client FROM CLIENT WHERE téléphone = ? AND id_client != ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "si", $telephone, $userId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = "Ce numéro de téléphone est déjà utilisé";
        }
    }
    
    // If no errors, update user
    if (empty($errors)) {
        $query = "UPDATE CLIENT SET nom = ?, prénom = ?, email = ?, téléphone = ? WHERE id_client = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssssi", $nom, $prenom, $email, $telephone, $userId);
        
        if (mysqli_stmt_execute($stmt)) {
            // Refresh user data
            $query = "SELECT * FROM CLIENT WHERE id_client = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $userId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);
            
            $success = "Profil mis à jour avec succès";
        } else {
            $errors[] = "Erreur lors de la mise à jour: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - AutoDrive</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <section class="profile-section">
        <div class="container">
            <div class="profile-content">
                <div class="sidebar">
                    <div class="user-info">
                        <div class="user-avatar">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <div class="user-name">
                            <h3><?php echo isset($user['prénom']) && $user['prénom'] ? $user['prénom'] . ' ' . $user['nom'] : $user['nom']; ?></h3>
                            <p><?php echo isset($user['email']) ? $user['email'] : ''; ?></p>
                        </div>
                    </div>
                    <ul class="sidebar-menu">
                        <li class="active"><a href="profile.php"><i class="fas fa-user"></i> Mon Profil</a></li>
                        <li><a href="reservations.php"><i class="fas fa-calendar-alt"></i> Mes Réservations</a></li>
                        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
                    </ul>
                </div>
                <div class="main-content">
                    <div class="section-header">
                        <h2>Mon Profil</h2>
                        <p>Gérez vos informations personnelles</p>
                    </div>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-error">
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success">
                            <?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="profile-form">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                            <div class="form-group">
                                <label for="nom">Nom*</label>
                                <input type="text" id="nom" name="nom" value="<?php echo isset($user['nom']) ? $user['nom'] : ''; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="prenom">Prénom</label>
                                <input type="text" id="prenom" name="prenom" value="<?php echo isset($user['prénom']) ? $user['prénom'] : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label for="email">Email*</label>
                                <input type="email" id="email" name="email" value="<?php echo isset($user['email']) ? $user['email'] : ''; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="telephone">Téléphone*</label>
                                <input type="tel" id="telephone" name="telephone" value="<?php echo isset($user['téléphone']) ? $user['téléphone'] : ''; ?>" required>
                            </div>
                            <div class="form-note">
                                <p>* Champs obligatoires</p>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Mettre à jour</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/main.js"></script>
</body>
</html>