<?php
include 'includes/config.php';

echo "<h1>Gear Data Test</h1>";

// Check if gear column exists
$check_column = "SHOW COLUMNS FROM voiture LIKE 'gear'";
$column_result = mysqli_query($conn, $check_column);

if (mysqli_num_rows($column_result) > 0) {
    echo "<p style='color: green;'>✓ Gear column exists in database</p>";
} else {
    echo "<p style='color: red;'>✗ Gear column does NOT exist in database</p>";
    echo "<p>You need to run this SQL command:</p>";
    echo "<code>ALTER TABLE voiture ADD COLUMN gear ENUM('automatique', 'manuel') DEFAULT NULL AFTER type;</code>";
}

// Show all cars with their gear data
echo "<h2>Current Cars and Gear Data:</h2>";
$query = "SELECT id_voiture, marque, modele, type, gear, statut FROM voiture ORDER BY id_voiture";
$result = mysqli_query($conn, $query);

if ($result) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>ID</th><th>Marque</th><th>Modele</th><th>Type</th><th>Gear</th><th>Statut</th>";
    echo "</tr>";
    
    while ($car = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $car['id_voiture'] . "</td>";
        echo "<td>" . htmlspecialchars($car['marque']) . "</td>";
        echo "<td>" . htmlspecialchars($car['modele']) . "</td>";
        echo "<td>" . htmlspecialchars($car['type']) . "</td>";
        echo "<td style='font-weight: bold; color: " . ($car['gear'] === 'automatique' ? 'blue' : 'green') . ";'>" . 
             htmlspecialchars($car['gear'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($car['statut']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>Error: " . mysqli_error($conn) . "</p>";
}

// Test the gear filter query
echo "<h2>Gear Filter Query Test:</h2>";
$gears_query = "SELECT DISTINCT gear FROM voiture WHERE gear IS NOT NULL AND gear != '' ORDER BY gear";
echo "<p><strong>Query:</strong> <code>$gears_query</code></p>";

$gears_result = mysqli_query($conn, $gears_query);
if ($gears_result) {
    echo "<p><strong>Results:</strong></p>";
    echo "<ul>";
    while ($gear = mysqli_fetch_assoc($gears_result)) {
        echo "<li><strong>" . htmlspecialchars($gear['gear']) . "</strong></li>";
    }
    echo "</ul>";
    
    if (mysqli_num_rows($gears_result) == 0) {
        echo "<p style='color: orange;'>No gear data found. All gear values are NULL or empty.</p>";
    }
} else {
    echo "<p style='color: red;'>Query Error: " . mysqli_error($conn) . "</p>";
}

// Quick fix buttons
echo "<h2>Quick Fix Options:</h2>";
echo "<div style='margin: 20px 0;'>";
echo "<button onclick='updateGearData()' style='padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; margin-right: 10px;'>Update Gear Data</button>";
echo "<button onclick='showSQL()' style='padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px;'>Show SQL Commands</button>";
echo "</div>";

echo "<div id='sqlCommands' style='display: none; background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>SQL Commands to Fix Gear Data:</h3>";
echo "<pre>";
echo "-- Add gear column (if not exists)\n";
echo "ALTER TABLE voiture ADD COLUMN gear ENUM('automatique', 'manuel') DEFAULT NULL AFTER type;\n\n";
echo "-- Update some cars to automatique\n";
echo "UPDATE voiture SET gear = 'automatique' WHERE id_voiture IN (1, 3, 5);\n\n";
echo "-- Update remaining cars to manuel\n";
echo "UPDATE voiture SET gear = 'manuel' WHERE gear IS NULL;\n\n";
echo "-- Verify the changes\n";
echo "SELECT id_voiture, marque, modele, gear FROM voiture;";
echo "</pre>";
echo "</div>";

mysqli_close($conn);
?>

<script>
function updateGearData() {
    if (confirm('This will update gear data for existing cars. Continue?')) {
        // You would need to create a separate PHP script to handle this
        alert('Please run the SQL commands manually in phpMyAdmin or your database tool.');
    }
}

function showSQL() {
    const sqlDiv = document.getElementById('sqlCommands');
    sqlDiv.style.display = sqlDiv.style.display === 'none' ? 'block' : 'none';
}
</script>

<style>
table { margin: 10px 0; }
th, td { padding: 8px 12px; text-align: left; }
code { background: #f1f1f1; padding: 2px 4px; border-radius: 3px; }
pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
</style>
