<?php
session_start();
include '../includes/config.php';
include '../includes/functions.php';

// Check if user is admin
if (!isAdmin()) {
    header("Location: ../login.php");
    exit();
}

// Handle form submission
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $marque = mysqli_real_escape_string($conn, $_POST['marque']);
    $modele = mysqli_real_escape_string($conn, $_POST['modele']);
    $immatriculation = mysqli_real_escape_string($conn, $_POST['immatriculation']);
    $type = mysqli_real_escape_string($conn, $_POST['type']);
    $nb_places = (int)$_POST['nb_places'];
    $prix_par_jour = (float)$_POST['prix_par_jour'];
    $statut = mysqli_real_escape_string($conn, $_POST['statut']);
    $image = mysqli_real_escape_string($conn, $_POST['image']);

    // Validate required fields
    if (empty($marque) || empty($modele) || empty($immatriculation) || empty($type) || empty($nb_places) || empty($prix_par_jour)) {
        $error = "Tous les champs obligatoires doivent être remplis";
    } else {
        // Insert into database
        $query = "INSERT INTO VOITURE (marque, modele, immatriculation, type, nb_places, prix_par_jour, statut, image) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($conn, $query);
        if ($stmt === false) {
            die('Erreur f prepare: ' . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt, "ssssidsi", $marque, $modele, $immatriculation, $type, $nb_places, $prix_par_jour, $statut, $image);

        if (mysqli_stmt_execute($stmt)) {
            $success = true;
        } else {
            $error = "Erreur lors de l'ajout de la voiture: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter une voiture - Administration</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="admin-body">
    <?php include 'includes/admin-header.php'; ?>

    <section class="admin-dashboard">
        <div class="container">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2>Ajouter une nouvelle voiture</h2>
                    <a href="cars.php" class="btn btn-outline">Retour à la liste</a>
                </div>
                <div class="admin-card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            La voiture a été ajoutée avec succès.
                            <a href="cars.php" class="btn btn-sm btn-primary">Retour à la liste</a>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-error">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form action="add-car.php" method="post" class="admin-form">
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
    </section>

    <?php include 'includes/admin-footer.php'; ?>
    <script src="../assets/js/main.js"></script>
</body>
</html>
