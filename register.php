<?php
session_start();
include 'includes/config.php';
include 'includes/functions.php';

// Check if user is already logged in
if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$nom = $prenom = $email = $telephone = $password = "";
$errors = [];

// Process registration form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom = sanitize($_POST["nom"]);
    $prenom = sanitize($_POST["prenom"]);
    $email = sanitize($_POST["email"]);
    $telephone = sanitize($_POST["telephone"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];
    
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
    
    if (empty($password)) {
        $errors[] = "Mot de passe est requis";
    } elseif (strlen($password) < 6) {
        $errors[] = "Le mot de passe doit contenir au moins 6 caractères";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Les mots de passe ne correspondent pas";
    }
    // Check if email already exists
    if (empty($errors)) {
        $query = "SELECT id_client FROM CLIENT WHERE email = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = "Cet email est déjà utilisé";
        }
    }
    
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - AutoDrive</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <section class="auth-section">
        <div class="container">
            <div class="auth-card">
                <div class="auth-header">
                    <h1>Inscription</h1>
                    <p>Créez votre compte pour réserver une voiture</p>
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
                
                <form action="register.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>" method="post" class="auth-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nom">Nom*</label>
                            <input type="text" id="nom" name="nom" value="<?php echo $nom; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="prenom">Prénom</label>
                            <input type="text" id="prenom" name="prenom" value="<?php echo $prenom; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email*</label>
                        <input type="email" id="email" name="email" value="<?php echo $email; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="telephone">Téléphone*</label>
                        <input type="tel" id="telephone" name="telephone" value="<?php echo $telephone; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Mot de passe*</label>
                        <div class="password-input">
                            <input type="password" id="password" name="password" required>
                            <i class="fas fa-eye toggle-password" data-target="password"></i>
                        </div>
                        <small>Le mot de passe doit contenir au moins 6 caractères</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirmer le mot de passe*</label>
                        <div class="password-input">
                            <input type="password" id="confirm_password" name="confirm_password" required>
                            <i class="fas fa-eye toggle-password" data-target="confirm_password"></i>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-block">S'inscrire</button>
                    </div>
                    
                    <div class="auth-links">
                        <p>Déjà un compte? <a href="login.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>">Se connecter</a></p>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    

</body>
</html>