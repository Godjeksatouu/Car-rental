<?php
session_start();
include '../includes/config.php';
include '../includes/functions.php';

// Check if user is already logged in as admin
if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit();
}

$username = $password = "";
$errors = [];

// Process login form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = sanitize($_POST["username"]);
    $password = $_POST["password"];
    
    // Validate inputs
    if (empty($username)) {
        $errors[] = "Nom d'utilisateur est requis";
    }
    
    if (empty($password)) {
        $errors[] = "Mot de passe est requis";
    }
    
    if (empty($errors)) {
        // Admin login
        $query = "SELECT * FROM admin WHERE nom_utilisateur = ?";
        $stmt = mysqli_prepare($conn, $query);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $username);
            
            if (!mysqli_stmt_execute($stmt)) {
                $errors[] = "Erreur d'exécution de la requête: " . mysqli_stmt_error($stmt);
            } else {
                $result = mysqli_stmt_get_result($stmt);
                
                if ($admin = mysqli_fetch_assoc($result)) {
                    // First try password_verify for hashed passwords
                    if (password_verify($password, $admin['mot_de_passe'])) {
                        $_SESSION['admin_id'] = $admin['id_admin'];
                        $_SESSION['admin_name'] = $admin['nom_utilisateur'];
                        header("Location: dashboard.php");
                        exit();
                    } 
                    // Then try direct comparison for plain text passwords (temporary solution)
                    else if ($password === $admin['mot_de_passe']) {
                        $_SESSION['admin_id'] = $admin['id_admin'];
                        $_SESSION['admin_name'] = $admin['nom_utilisateur'];
                        header("Location: dashboard.php");
                        exit();
                    }
                }
                $errors[] = "Nom d'utilisateur ou mot de passe incorrect";
            }
            mysqli_stmt_close($stmt);
        }
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
    <link rel="stylesheet" href="../assets/css/admin-login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="admin-login-page">
        <div class="admin-login-card">
            <div class="admin-login-logo">
                <i class="fas fa-car"></i>
                <h1>AutoDrive</h1>
                <span>Administration</span>
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
            
            <form action="login.php" method="post" class="admin-login-form">
                <div class="form-group">
                    <label for="username">Nom d'utilisateur</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <div class="password-input">
                        <input type="password" id="password" name="password" required>
                        <i class="fas fa-eye toggle-password" data-target="password"></i>
                    </div>
                </div>
                <button type="submit">Connexion</button>
            </form>
            
            <div class="admin-login-footer">
                <a href="<?php echo $base_url; ?>index.php"><i class="fas fa-arrow-left"></i> Retour au site principal</a>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.querySelector('.toggle-password');
            const passwordField = document.getElementById('password');
            
            togglePassword.addEventListener('click', function() {
                const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordField.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        });
    </script>
</body>
</html>