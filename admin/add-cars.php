<?php
session_start();
include '../includes/config.php';
include '../includes/functions.php';

// Redirect if not admin
if (!isAdmin()) {
    header("Location: ../login.php");
    exit();
}

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $marque = trim($_POST['marque'] ?? '');
    $modele = trim($_POST['modele'] ?? '');
    $immatriculation = trim($_POST['immatriculation'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $nb_places = (int)($_POST['nb_places'] ?? 0);
    $prix_par_jour = (float)($_POST['prix_par_jour'] ?? 0);
    $statut = trim($_POST['statut'] ?? 'disponible');
    $gear = trim($_POST['gear'] ?? 'manuel');
    $image = trim($_POST['image'] ?? '');

    if (!$marque || !$modele || !$immatriculation || !$type || !$nb_places || !$prix_par_jour || !$gear) {
        $error = "Tous les champs obligatoires doivent être remplis.";
    } else {
        $query = "INSERT INTO VOITURE (marque, modele, immatriculation, type, nb_places, prix_par_jour, statut, gear, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ssssidsss", $marque, $modele, $immatriculation, $type, $nb_places, $prix_par_jour, $statut, $gear, $image);
            if (mysqli_stmt_execute($stmt)) {
                $success = true;
            } else {
                $error = "Erreur lors de l'ajout: " . mysqli_error($conn);
            }
        } else {
            $error = "Erreur de préparation de la requête.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter une voiture - Administration</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/admin-header.php'; ?>
    <main class="admin-dashboard">
        <div class="container">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2>Ajouter une nouvelle voiture</h2>
                    <a href="cars.php" class="btn btn-outline">Retour à la liste</a>
                </div>
                <div class="admin-card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success">Voiture ajoutée avec succès.</div>
                    <?php elseif ($error): ?>
                        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <form action="" method="post" class="admin-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="marque">Marque*</label>
                                <input type="text" id="marque" name="marque" required>
                            </div>
                            <div class="form-group">
                                <label for="modele">Modèle*</label>
                                <input type="text" id="modele" name="modele" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="immatriculation">Immatriculation*</label>
                                <input type="text" id="immatriculation" name="immatriculation" required>
                            </div>
                            <div class="form-group">
                                <label for="type">Type*</label>
                                <select id="type" name="type" required>
                                    <option value="">Sélectionner un type</option>
                                    <option value="diesel">Diesel</option>
                                    <option value="essence">Essence</option>
                                    <option value="hybride">Hybride</option>
                                    <option value="electrique">Électrique</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nb_places">Nombre de places*</label>
                                <input type="number" id="nb_places" name="nb_places" min="1" max="9" required>
                            </div>
                            <div class="form-group">
                                <label for="prix_par_jour">Prix par jour (€)*</label>
                                <input type="number" id="prix_par_jour" name="prix_par_jour" min="0" step="0.01" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="statut">Statut</label>
                                <select id="statut" name="statut">
                                    <option value="disponible">Disponible</option>
                                    <option value="réservé">Réservé</option>
                                    <option value="maintenance">En maintenance</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="gear">Boîte de vitesses*</label>
                                <select id="gear" name="gear" required>
                                    <option value="">Sélectionner une boîte</option>
                                    <option value="manuel">Manuel</option>
                                    <option value="automatique">Automatique</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="image">URL de l'image</label>
                                <input type="url" id="image" name="image" placeholder="https://example.com/image.jpg">
                                <small>Laissez vide pour utiliser l'image par défaut</small>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Ajouter la voiture</button>
                            <a href="cars.php" class="btn btn-outline">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
    <?php include 'includes/admin-footer.php'; ?>
    <script src="../assets/js/main.js" defer></script>
</body>
</html>