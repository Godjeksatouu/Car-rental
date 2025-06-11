<div class="car-card">
    <div class="car-image">
        <?php
        $image = $car['image'] ? $car['image'] : 'https://images.pexels.com/photos/170811/pexels-photo-170811.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1';
        ?>
        <img src="<?php echo $image; ?>" alt="<?php echo $car['marque'] . ' ' . $car['modele']; ?>">
        <div class="car-status <?php echo $car['statut']; ?>"><?php echo ucfirst($car['statut']); ?></div>
    </div>
    <div class="car-info">
        <div class="car-title">
            <h3><?php echo $car['marque'] . ' ' . $car['modele']; ?></h3>
            <p class="car-immatriculation"><?php echo $car['immatriculation']; ?></p>
        </div>
        <div class="car-features">
            <div class="feature">
                <i class="fas fa-gas-pump"></i>
                <span><?php echo ucfirst($car['type']); ?></span>
            </div>
            <div class="feature">
                <i class="fas fa-users"></i>
                <span><?php echo $car['nb_places']; ?> places</span>
            </div>
            <div class="feature">
                <?php if ($car['type'] == 'electrique'): ?>
                    <i class="fas fa-bolt"></i>
                    <span>Électrique</span>
                <?php else: ?>
                    <i class="fas fa-tachometer-alt"></i>
                    <span><?php echo isset($car['carburant']) ? ucfirst($car['carburant']) : ucfirst($car['type']); ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="car-price">
            <div class="price-tag">
                <span class="price"><?php echo $car['prix_par_jour']; ?> €</span>
                <span class="period">/ jour</span>
            </div>
            <a href="reservation.php?id=<?php echo $car['id_voiture']; ?>" class="btn btn-success"><i class="fas fa-calendar-check"></i> Réservé</a>
        </div>
    </div>
</div>