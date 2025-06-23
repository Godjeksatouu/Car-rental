<?php
session_start();
include 'includes/config.php';
include 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    die("❌ Vous devez être connecté pour utiliser cet outil de diagnostic");
}

$userId = $_SESSION['user_id'];

echo "<h1>🔍 Diagnostic du Système de Paiement</h1>";
echo "<p>Cet outil aide à diagnostiquer les problèmes de paiement dans le système.</p>";

// Check database connection
echo "<h2>1. Connexion à la base de données</h2>";
if ($conn) {
    echo "✅ Connexion à la base de données réussie<br>";
} else {
    echo "❌ Erreur de connexion à la base de données: " . mysqli_connect_error() . "<br>";
    exit;
}

// Check user's reservations
echo "<h2>2. Vos réservations</h2>";
$query = "SELECT r.id_reservation, r.date_debut, r.date_fin, v.marque, v.modele, l.id_location, l.ETAT_PAIEMENT 
          FROM RESERVATION r 
          JOIN VOITURE v ON r.id_voiture = v.id_voiture 
          LEFT JOIN LOCATION l ON r.id_reservation = l.id_reservation 
          WHERE r.id_client = ? 
          ORDER BY r.date_debut DESC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID Réservation</th><th>Véhicule</th><th>Dates</th><th>ID Location</th><th>Statut Paiement</th><th>Action</th></tr>";
    
    while ($reservation = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $reservation['id_reservation'] . "</td>";
        echo "<td>" . $reservation['marque'] . " " . $reservation['modele'] . "</td>";
        echo "<td>" . $reservation['date_debut'] . " → " . $reservation['date_fin'] . "</td>";
        
        if (is_null($reservation['id_location'])) {
            echo "<td style='color: red;'>❌ MANQUANT</td>";
            echo "<td style='color: red;'>Non payé (pas de location)</td>";
            echo "<td><a href='?fix=" . $reservation['id_reservation'] . "' style='background: orange; color: white; padding: 5px; text-decoration: none;'>🔧 Corriger</a></td>";
        } else {
            echo "<td style='color: green;'>✅ " . $reservation['id_location'] . "</td>";
            echo "<td>" . ($reservation['ETAT_PAIEMENT'] ? "✅ Payé" : "⏳ Non payé") . "</td>";
            if (!$reservation['ETAT_PAIEMENT']) {
                echo "<td><a href='payment.php?id=" . $reservation['id_location'] . "' style='background: green; color: white; padding: 5px; text-decoration: none;'>💳 Payer</a></td>";
            } else {
                echo "<td>✅ Payé</td>";
            }
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "ℹ️ Aucune réservation trouvée pour votre compte.";
}

// Handle fix request
if (isset($_GET['fix'])) {
    $reservationId = (int)$_GET['fix'];
    echo "<h2>3. Correction en cours...</h2>";
    
    $locationId = ensureLocationExists($reservationId, $conn);
    if ($locationId) {
        echo "✅ Enregistrement LOCATION créé avec succès (ID: $locationId)<br>";
        echo "<a href='payment.php?id=$locationId' style='background: green; color: white; padding: 10px; text-decoration: none; display: inline-block; margin-top: 10px;'>💳 Procéder au paiement</a>";
    } else {
        echo "❌ Erreur lors de la création de l'enregistrement LOCATION<br>";
    }
}

echo "<h2>4. Liens utiles</h2>";
echo "<a href='reservations.php'>📋 Mes réservations</a> | ";
echo "<a href='profile.php'>👤 Mon profil</a> | ";
echo "<a href='cars.php'>🚗 Voir les voitures</a>";

echo "<hr>";
echo "<p><small>🔧 Outil de diagnostic - Version 1.0</small></p>";
?>
