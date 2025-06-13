<?php
session_start();
include 'includes/config.php';
include 'includes/functions.php';

// Check if user is already logged in
if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$email = $password = "";
$errors = [];
$isAdmin = isset($_GET['admin']) ? true : false;

// Process login form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = sanitize($_POST["email"]);
    $password = $_POST["password"];
    
    // Validate inputs
    if (empty($email)) {
        $errors[] = "Email est requis";
    }
    
    if (empty($password)) {
        $errors[] = "Mot de passe est requis";
    }
    
    if (empty($errors)) {
        if ($isAdmin) {
            // Admin login
            $query = "SELECT * FROM ADMIN WHERE email = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($admin = mysqli_fetch_assoc($result)) {
                if (isset($admin['mot_de_passe']) && password_verify($password, $admin['mot_de_passe'])) {
                    $_SESSION['admin_id'] = $admin['id_admin'];
                    header("Location: admin/dashboard.php");
                    exit();
                }
            }
            $errors[] = "Email ou mot de passe incorrect";
        } else {
            // Client login
            $query = "SELECT * FROM CLIENT WHERE email = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($user = mysqli_fetch_assoc($result)) {
                if (isset($user['mot_de_passe']) && password_verify($password, $user['mot_de_passe'])) {
                    $_SESSION['user_id'] = $user['id_client'];
                    
                    // Redirect to requested page or homepage
                    $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';
                    header("Location: $redirect");
                    exit();
                }
            }
            $errors[] = "Email ou mot de passe incorrect";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isAdmin ? 'Administration' : 'Connexion'; ?> - AutoDrive</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <section class="auth-section">
        <div class="container">
            <div class="auth-card <?php echo $isAdmin ? 'admin-login' : ''; ?>">
                <div class="auth-header">
                    <h1><?php echo $isAdmin ? 'Administration' : 'Connexion'; ?></h1>
                    <p><?php echo $isAdmin ? 'Connectez-vous à l\'interface d\'administration' : 'Connectez-vous pour réserver une voiture'; ?></p>
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
                
                <form action="login.php<?php echo $isAdmin ? '?admin=1' : (isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''); ?>" method="post" class="auth-form">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo $email; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Mot de passe</label>
                        <div class="password-input">
                            <input type="password" id="password" name="password" required>
                            <i class="fas fa-eye toggle-password" data-target="password"></i>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-block"><?php echo $isAdmin ? 'Accéder à l\'administration' : 'Se connecter'; ?></button>
                    </div>
                    
                    <div class="auth-links">
                        <?php if (!$isAdmin): ?>
                            <p>Vous n'avez pas de compte ? <a href="register.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>">Inscrivez-vous</a></p>
                            <p class="admin-link"><a href="login.php?admin=1">Accès administration</a></p>
                        <?php else: ?>
                            <p class="admin-link"><a href="login.php">Retour à la connexion client</a></p>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/main.js"></script>
</body>
</html>