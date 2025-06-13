<?php
session_start();

// Use absolute paths to ensure files are found
$basePath = $_SERVER['DOCUMENT_ROOT'] . '/Autodrive/Car-rental/';
require_once($basePath . 'includes/config.php');
require_once($basePath . 'includes/functions.php');

// Check if user is already logged in
if (isLoggedIn()) {
    header("Location: ../index.php");
    exit();
}

$email = $password = "";
$errors = [];
$isAdmin = true; // Always true since this is admin login page

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
        // Admin login
        $query = "SELECT * FROM ADMIN WHERE email = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($admin = mysqli_fetch_assoc($result)) {
            if (isset($admin['mot_de_passe']) && password_verify($password, $admin['mot_de_passe'])) {
                $_SESSION['admin_id'] = $admin['id_admin'];
                header("Location: dashboard.php");
                exit();
            }
        }
        $errors[] = "Email ou mot de passe incorrect";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - AutoDrive</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include $basePath . 'includes/header.php'; ?>

    <section class="auth-section">
        <div class="container">
            <div class="auth-card admin-login">
                <div class="auth-header">
                    <h1>Administration</h1>
                    <p>Connectez-vous à l'interface d'administration</p>
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
                
                <form action="login.php" method="post" class="auth-form">
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
                        <button type="submit" class="btn btn-primary btn-block">Accéder à l'administration</button>
                    </div>
                    
                    <div class="auth-links">
                        <p class="admin-link"><a href="../login.php">Retour à la connexion client</a></p>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <?php include $basePath . 'includes/footer.php'; ?>
    <script src="../assets/js/main.js"></script>
</body>
</html>