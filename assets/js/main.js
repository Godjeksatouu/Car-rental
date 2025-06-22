document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const mobileMenu = document.querySelector('.mobile-menu');
    
    if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', function() {
            mobileMenu.classList.toggle('active');
            document.body.classList.toggle('no-scroll');
        });
    }
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 300);
        }, 5000);
    });
    
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // Reservation date validation
    const dateDebutInput = document.getElementById('date_debut');
    const dateFinInput = document.getElementById('date_fin');
    
    if (dateDebutInput && dateFinInput) {
        dateDebutInput.addEventListener('change', function() {
            const today = new Date().toISOString().split('T')[0];
            if (this.value < today) {
                this.value = today;
            }
            
            if (dateFinInput.value && dateFinInput.value < this.value) {
                dateFinInput.value = this.value;
            }
            
            dateFinInput.min = this.value;
        });
    }
    
    // Add animation to elements when they come into view
    const animateOnScroll = function() {
        const elements = document.querySelectorAll('.service-card, .car-card, .partner');
        
        elements.forEach(element => {
            const elementPosition = element.getBoundingClientRect().top;
            const screenPosition = window.innerHeight;
            
            if (elementPosition < screenPosition) {
                element.classList.add('animate');
            }
        });
    };
    
    window.addEventListener('scroll', animateOnScroll);
    animateOnScroll();

    // User dropdown functionality
    const dropdown = document.querySelector('.dropdown');
    const dropdownBtn = document.querySelector('.dropdown-btn');
    const dropdownContent = document.querySelector('.dropdown-content');

    if (dropdown && dropdownBtn && dropdownContent) {
        // Toggle dropdown on button click
        dropdownBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            dropdown.classList.toggle('active');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!dropdown.contains(e.target)) {
                dropdown.classList.remove('active');
            }
        });

        // Close dropdown when pressing Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                dropdown.classList.remove('active');
            }
        });

        // Prevent dropdown from closing when clicking inside it
        dropdownContent.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }

});
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Litepicker
    const picker = new Litepicker({
        element: document.getElementById('date_range'),
        singleMode: false,
        format: 'DD/MM/YYYY',
        delimiter: ' - ',
        minDate: new Date(),
        onSelect: function(start, end) {
            // Update hidden inputs when dates are selected
            document.getElementById('date_debut').value = start.format('YYYY-MM-DD');
            document.getElementById('date_fin').value = end.format('YYYY-MM-DD');
            
            // Calculate and display price
            updatePrice(start, end);
        }
    });

    // Function to calculate and update price
    function updatePrice(startDate, endDate) {
        const pricePerDay = <?php echo $car['prix_par_jour']; ?>;
        const diffTime = Math.abs(endDate - startDate);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
        const totalPrice = (pricePerDay * diffDays).toFixed(2);
        
        // Update price display
        document.getElementById('daysCount').textContent = diffDays;
        document.getElementById('totalPrice').textContent = totalPrice + ' €';
        document.getElementById('priceEstimate').style.display = 'block';
    }

    // Manual date input fallback
    document.getElementById('toggle-manual').addEventListener('click', function() {
        const manualDates = document.getElementById('manual-dates');
        if (manualDates.style.display === 'none') {
            manualDates.style.display = 'block';
            this.innerHTML = '<i class="fas fa-times"></i> Masquer saisie manuelle';
        } else {
            manualDates.style.display = 'none';
            this.innerHTML = '<i class="fas fa-keyboard"></i> Saisie manuelle';
        }
    });

    // Update dates when manual inputs change
    document.getElementById('manual_start').addEventListener('change', function() {
        const startDate = this.value;
        const endInput = document.getElementById('manual_end');
        
        if (startDate) {
            // Set minimum end date
            const nextDay = new Date(startDate);
            nextDay.setDate(nextDay.getDate() + 1);
            endInput.min = nextDay.toISOString().split('T')[0];
            
            updateManualDates();
        }
    });

    document.getElementById('manual_end').addEventListener('change', updateManualDates);

    function updateManualDates() {
        const startDate = document.getElementById('manual_start').value;
        const endDate = document.getElementById('manual_end').value;

        if (startDate && endDate) {
            // Update hidden inputs
            document.getElementById('date_debut').value = startDate;
            document.getElementById('date_fin').value = endDate;
            
            // Update price
            updatePrice(new Date(startDate), new Date(endDate));
            
            // Update display input
            const startFormatted = new Date(startDate).toLocaleDateString('fr-FR');
            const endFormatted = new Date(endDate).toLocaleDateString('fr-FR');
            document.getElementById('date_range').value = startFormatted + ' - ' + endFormatted;
        }
    }
});