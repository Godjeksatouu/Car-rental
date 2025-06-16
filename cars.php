
 <?php
session_start();
include 'includes/config.php'; // اتصال الداتا بيز
include 'includes/functions.php'; // أي دوال عندك

// نجيب كل السيارات من الداتا بيز (بدون فلترة في PHP)
$query = "SELECT * FROM VOITURE ORDER BY prix_par_jour ASC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Nos Voitures - AutoDrive</title>
    <link rel="stylesheet" href="assets/css/style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <section class="page-header">
        <div class="container">
            <h1>Nos Voitures</h1>
            <p>Trouvez la voiture parfaite pour vos besoins</p>
        </div>
    </section>

    <section class="cars-section">
        <div class="container">
            <div class="cars-content">
                <div class="filters">
                    <h3><i class="fas fa-filter"></i> Filtres</h3>
                    <form id="filter-form">
                        <div class="filter-group">
                            <label for="marque">Marque</label>
                            <select name="marque" id="marque">
                                <option value="">Toutes les marques</option>
                                <?php
                                $marqueQuery = "SELECT DISTINCT marque FROM VOITURE ORDER BY marque";
                                $marqueResult = mysqli_query($conn, $marqueQuery);
                                while ($marque = mysqli_fetch_assoc($marqueResult)) {
                                    echo '<option value="'.htmlspecialchars($marque['marque']).'">'.htmlspecialchars($marque['marque']).'</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="type">Type</label>
                            <select name="type" id="type">
                                <option value="">Tous les types</option>
                                <option value="diesel">Diesel</option>
                                <option value="essence">Essence</option>
                                <option value="hybride">Hybride</option>
                                <option value="electrique">Électrique</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="prix_min">Prix minimum (€/jour)</label>
                            <input type="number" id="prix_min" name="prix_min" min="0" />
                        </div>
                        <div class="filter-group">
                            <label for="prix_max">Prix maximum (€/jour)</label>
                            <input type="number" id="prix_max" name="prix_max" min="0" />
                        </div>
                        <button type="submit" class="btn btn-primary">Appliquer les filtres</button>
                        <button type="button" id="reset-filters" class="btn btn-outline">Réinitialiser</button>
                    </form>
                </div>

                <div class="cars-list">
                    <div class="results-count">
                        <p><i class="fas fa-car"></i> <span id="cars-count"><?php echo mysqli_num_rows($result); ?></span> voiture(s) trouvée(s)</p>
                    </div>
                    <div class="cars-grid" id="cars-grid">
                        <?php
                        if (mysqli_num_rows($result) > 0) {
                            while ($car = mysqli_fetch_assoc($result)) {
                                include 'includes/car-card.php';  // تأكد أن car-card.php يستقبل $car ويعطي data-* attributes كما في الرد السابق
                            }
                        } else {
                            echo '<div class="no-results"><i class="fas fa-search"></i>Aucune voiture disponible.</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script src="https://kit.fontawesome.com/a076d05399.js"></script>
     <script src="assets/js/main.js"></script>  <!-- ملف جافاسكريبت اللي درناه للفيلتر -->
    <script>
        // Reset filter button behavior
        document.getElementById('reset-filters').addEventListener('click', () => {
            document.getElementById('filter-form').reset();
            document.getElementById('filter-form').dispatchEvent(new Event('submit'));
        });
    </script>
</body>
</html>
