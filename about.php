<?php
session_start();
include 'includes/config.php';
include 'includes/functions.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>À Propos de Nous - AutoDrive</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <section class="page-header">
        <div class="container">
            <h1>À Propos de Nous</h1>
            <p>Découvrez l'histoire, les valeurs et l'équipe derrière AutoDrive</p>
        </div>
    </section>

    <section class="about-section">
        <div class="container">
            <div class="about-content">
                <div class="about-image">
                    <img src="https://images.pexels.com/photos/3807386/pexels-photo-3807386.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1" alt="AutoDrive Histoire">
                </div>
                <div class="about-text">
                    <h2><i class="fas fa-history"></i> Notre Histoire</h2>
                    <p>Fondée en 2010, AutoDrive est née d'une vision simple : rendre la location de voitures accessible, transparente et agréable pour tous. Ce qui a commencé comme une petite entreprise familiale avec seulement 5 véhicules s'est transformée en l'une des sociétés de location les plus fiables du Maroc.</p>
                    <p>Au fil des années, nous avons constamment évolué pour répondre aux besoins changeants de nos clients, en élargissant notre flotte et en améliorant nos services. Aujourd'hui, nous sommes fiers de proposer plus de 100 véhicules modernes et bien entretenus, allant des citadines économiques aux SUV luxueux.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="mission-section">
        <div class="container">
            <div class="mission-content">
                <div class="mission-text">
                    <h2><i class="fas fa-bullseye"></i> Notre Mission</h2>
                    <p>Chez AutoDrive, notre mission est de fournir des solutions de mobilité exceptionnelles qui dépassent les attentes de nos clients. Nous nous engageons à offrir :</p>
                    <ul class="mission-list">
                        <li><i class="fas fa-check-circle"></i> Des véhicules de qualité supérieure, régulièrement entretenus et nettoyés</li>
                        <li><i class="fas fa-check-circle"></i> Un service client attentif et réactif, disponible 24h/24 et 7j/7</li>
                        <li><i class="fas fa-check-circle"></i> Des tarifs transparents, sans frais cachés</li>
                        <li><i class="fas fa-check-circle"></i> Des processus de réservation et de retour simples et efficaces</li>
                        <li><i class="fas fa-check-circle"></i> Des solutions adaptées à tous les besoins de mobilité</li>
                    </ul>
                </div>
                <div class="mission-image">
                    <img src="https://images.pexels.com/photos/3806288/pexels-photo-3806288.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1" alt="AutoDrive Mission">
                </div>
            </div>
        </div>
    </section>

    <section class="values-section">
        <div class="container">
            <h2 class="section-title"><i class="fas fa-star"></i> Nos Valeurs</h2>
            <div class="values-grid">
                <div class="value-card">
                    <div class="icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <h3>Confiance</h3>
                    <p>Nous bâtissons des relations durables basées sur la confiance et la transparence avec nos clients et partenaires.</p>
                </div>
                
                <div class="value-card">
                    <div class="icon">
                        <i class="fas fa-award"></i>
                    </div>
                    <h3>Excellence</h3>
                    <p>Nous visons l'excellence dans tous les aspects de notre service, de la qualité de nos véhicules à l'expérience client.</p>
                </div>
                
                <div class="value-card">
                    <div class="icon">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <h3>Durabilité</h3>
                    <p>Nous nous engageons à réduire notre impact environnemental en investissant dans des véhicules économes en carburant et électriques.</p>
                </div>
                
                <div class="value-card">
                    <div class="icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Communauté</h3>
                    <p>Nous soutenons activement les communautés locales et contribuons à leur développement économique.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="team-section">
        <div class="container">
            <h2 class="section-title"><i class="fas fa-user-friends"></i> Notre Équipe</h2>
            <div class="team-grid">
                <div class="team-member">
                    <div class="member-image">
                        <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="Ahmed Benali">
                    </div>
                    <div class="member-info">
                        <h3>Ahmed Benali</h3>
                        <span class="position">Fondateur & Directeur</span>
                        <p>Avec plus de 20 ans d'expérience dans l'industrie automobile, Ahmed a fondé AutoDrive avec la vision de transformer l'expérience de location de voitures au Maroc.</p>
                        <div class="social-links">
                            <a href="#"><i class="fab fa-linkedin"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="team-member">
                    <div class="member-image">
                        <img src="https://randomuser.me/api/portraits/women/44.jpg" alt="Sophia Kadiri">
                    </div>
                    <div class="member-info">
                        <h3>Sophia Kadiri</h3>
                        <span class="position">Directrice des Opérations</span>
                        <p>Sophia supervise toutes les opérations quotidiennes, assurant que chaque client reçoive un service exceptionnel et que notre flotte soit toujours en parfait état.</p>
                        <div class="social-links">
                            <a href="#"><i class="fab fa-linkedin"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="team-member">
                    <div class="member-image">
                        <img src="https://randomuser.me/api/portraits/men/22.jpg" alt="Karim Tazi">
                    </div>
                    <div class="member-info">
                        <h3>Karim Tazi</h3>
                        <span class="position">Responsable Service Client</span>
                        <p>Karim et son équipe sont dédiés à offrir une assistance exceptionnelle à nos clients, résolvant rapidement tout problème et assurant une expérience sans souci.</p>
                        <div class="social-links">
                            <a href="#"><i class="fab fa-linkedin"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="testimonials-section">
        <div class="container">
            <h2 class="section-title"><i class="fas fa-quote-left"></i> Ce Que Disent Nos Clients</h2>
            <div class="testimonials-grid">
                <div class="testimonial-card">
                    <div class="testimonial-content">
                        <div class="rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p>"J'utilise AutoDrive depuis plus de 3 ans maintenant et je n'ai jamais été déçu. Les voitures sont toujours propres et bien entretenues, et le personnel est extrêmement serviable."</p>
                    </div>
                    <div class="testimonial-author">
                        <img src="https://randomuser.me/api/portraits/men/75.jpg" alt="Mohamed L.">
                        <div>
                            <h4>Mohamed L.</h4>
                            <span>Client fidèle</span>
                        </div>
                    </div>
                </div>
                
                <div class="testimonial-card">
                    <div class="testimonial-content">
                        <div class="rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p>"Le processus de réservation est si simple et rapide ! J'ai pu réserver une voiture en quelques minutes et la récupérer le jour même. Le service client est également excellent."</p>
                    </div>
                    <div class="testimonial-author">
                        <img src="https://randomuser.me/api/portraits/women/63.jpg" alt="Leila M.">
                        <div>
                            <h4>Leila M.</h4>
                            <span>Cliente régulière</span>
                        </div>
                    </div>
                </div>
                
                <div class="testimonial-card">
                    <div class="testimonial-content">
                        <div class="rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                        </div>
                        <p>"En tant qu'entreprise, nous utilisons AutoDrive pour tous nos besoins de mobilité. Leur service de location longue durée est parfait pour nous, et leur équipe est toujours prête à s'adapter à nos besoins."</p>
                    </div>
                    <div class="testimonial-author">
                        <img src="https://randomuser.me/api/portraits/men/41.jpg" alt="Youssef B.">
                        <div>
                            <h4>Youssef B.</h4>
                            <span>Client professionnel</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="cta">
        <div class="container">
            <div class="cta-content">
                <h2>Prêt à nous faire confiance ?</h2>
                <p>Découvrez notre flotte de véhicules et réservez dès maintenant</p>
                <a href="cars.php" class="btn btn-light">Voir nos voitures</a>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/main.js"></script>
</body>
</html>