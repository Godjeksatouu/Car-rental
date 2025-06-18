<?php
session_start();
include '../includes/config.php';

// Use your actual primary key column name, e.g., id_voiture
$primaryKey = 'id_voiture';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: cars.php');
    exit();
}

$id = (int)$_GET['id'];
$error = '';
$success = false;

// Fetch car data
$stmt = $conn->prepare("SELECT * FROM VOITURE WHERE $primaryKey = ?");
if (!$stmt) {
    die("Erreur de préparation de la requête : " . $conn->error);
}
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$car = $result->fetch_assoc();

if (!$car) {
    $error = "Voiture introuvable.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $car) {
    $marque = trim($_POST['marque']);
    $modele = trim($_POST['modele']);
    $immatriculation = trim($_POST['immatriculation']);
    $type = trim($_POST['type']);
    $nb_places = (int)$_POST['nb_places'];
    $prix_par_jour = (float)$_POST['prix_par_jour'];
    $statut = trim($_POST['statut']);
    $gear = trim($_POST['gear']);
    $image = trim($_POST['image']);

    if (!$marque || !$modele || !$immatriculation || !$type || !$nb_places || !$prix_par_jour || !$gear) {
        $error = "Tous les champs obligatoires doivent être remplis.";
    } else {
        $stmt = $conn->prepare("UPDATE VOITURE SET marque=?, modele=?, immatriculation=?, type=?, nb_places=?, prix_par_jour=?, statut=?, gear=?, image=? WHERE $primaryKey=?");
        $stmt->bind_param("ssssidsssi", $marque, $modele, $immatriculation, $type, $nb_places, $prix_par_jour, $statut, $gear, $image, $id);
        if ($stmt->execute()) {
            $success = true;
            // Refresh car data
            $car = [
                'marque' => $marque,
                'modele' => $modele,
                'immatriculation' => $immatriculation,
                'type' => $type,
                'nb_places' => $nb_places,
                'prix_par_jour' => $prix_par_jour,
                'statut' => $statut,
                'gear' => $gear,
                'image' => $image
            ];
        } else {
            $error = "Erreur lors de la mise à jour.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier une voiture - Administration</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <main class="admin-dashboard">
    <div class="container">
        <div class="admin-card">
            <div class="admin-card-header">
                <h2><i class="fa fa-edit"></i> Modifier la voiture</h2>
                <a href="cars.php" class="btn btn-outline"><i class="fa fa-arrow-left"></i> Retour à la liste</a>
            </div>
            <div class="admin-card-body">
                <?php if ($error): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                <?php elseif ($success): ?>
                    <div class="alert alert-success">Voiture mise à jour avec succès.</div>
                <?php endif; ?>
                <?php if ($car): ?>
                <form action="" method="post" class="admin-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="marque"><i class="fa fa-car"></i> Marque*</label>
                            <input type="text" id="marque" name="marque" value="<?= htmlspecialchars($car['marque']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="modele"><i class="fa fa-tag"></i> Modèle*</label>
                            <input type="text" id="modele" name="modele" value="<?= htmlspecialchars($car['modele']) ?>" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="immatriculation"><i class="fa fa-id-card"></i> Immatriculation*</label>
                            <input type="text" id="immatriculation" name="immatriculation" value="<?= htmlspecialchars($car['immatriculation']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="type"><i class="fa fa-cogs"></i> Type*</label>
                            <select id="type" name="type" required>
                                <option value="diesel" <?= $car['type']=='diesel'?'selected':'' ?>>Diesel</option>
                                <option value="essence" <?= $car['type']=='essence'?'selected':'' ?>>Essence</option>
                                <option value="hybride" <?= $car['type']=='hybride'?'selected':'' ?>>Hybride</option>
                                <option value="electrique" <?= $car['type']=='electrique'?'selected':'' ?>>Électrique</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nb_places"><i class="fa fa-users"></i> Nombre de places*</label>
                            <input type="number" id="nb_places" name="nb_places" min="1" max="9" value="<?= htmlspecialchars($car['nb_places']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="prix_par_jour"><i class="fa fa-euro-sign"></i> Prix par jour (€)*</label>
                            <input type="number" id="prix_par_jour" name="prix_par_jour" min="0" step="0.01" value="<?= htmlspecialchars($car['prix_par_jour']) ?>" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="statut"><i class="fa fa-info-circle"></i> Statut</label>
                            <select id="statut" name="statut">
                                <option value="disponible" <?= $car['statut']=='disponible'?'selected':'' ?>>Disponible</option>
                                <option value="réservé" <?= $car['statut']=='réservé'?'selected':'' ?>>Réservé</option>
                                <option value="maintenance" <?= $car['statut']=='maintenance'?'selected':'' ?>>En maintenance</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="gear"><i class="fa fa-cogs"></i> Boîte de vitesses*</label>
                            <select id="gear" name="gear" required>
                                <option value="">Sélectionner une boîte</option>
                                <option value="manuel" <?= ($car['gear']??'')=='manuel'?'selected':'' ?>>Manuel</option>
                                <option value="automatique" <?= ($car['gear']??'')=='automatique'?'selected':'' ?>>Automatique</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="image"><i class="fa fa-image"></i> URL de l'image</label>
                            <input type="url" id="image" name="image" value="<?= htmlspecialchars($car['image']) ?>" placeholder="https://example.com/image.jpg">
                            <small>Laissez vide pour utiliser l'image par défaut</small>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Enregistrer</button>
                        <a href="cars.php" class="btn btn-outline">Annuler</a>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
    <?php include 'includes/admin-footer.php'; ?>
    <script src="../assets/js/main.js" defer></script>
</body>
</html>