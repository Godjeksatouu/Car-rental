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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-login-page {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('https://images.pexels.com/photos/3807386/pexels-photo-3807386.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1') center/cover;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .admin-login-card {
            background-color: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            padding: 40px;
            max-width: 450px;
            width: 100%;
            border-top: 5px solid var(--primary-color);
        }
        
        .admin-login-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .admin-login-logo i {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        .admin-login-logo h1 {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .admin-login-logo span {
            display: inline-block;
            background-color: var(--primary-color);
            color: white;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .admin-login-form .form-group {
            margin-bottom: 20px;
        }
        
        .admin-login-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--gray-700);
        }
        
        .admin-login-form input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--gray-300);
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .admin-login-form input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(53, 99, 233, 0.2);
            outline: none;
        }
        
        .admin-login-form button {
            width: 100%;
            padding: 14px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .admin-login-form button:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .admin-login-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--gray-200);
        }
        
        .admin-login-footer a {
            color: var(--gray-600);
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .admin-login-footer a:hover {
            color: var(--primary-color);
        }
        
        .password-input {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--gray-500);
        }
    </style>
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