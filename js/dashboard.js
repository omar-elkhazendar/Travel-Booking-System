document.addEventListener('DOMContentLoaded', function() {
    // Handle search form submission
    const searchForm = document.querySelector('.search-filters form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const destination = this.querySelector('[name="destination"]').value;
            const departureDate = this.querySelector('[name="departure_date"]').value;
            
            // Filter flights based on search criteria
            const flightCards = document.querySelectorAll('.flight-card');
            flightCards.forEach(card => {
                const cardDestination = card.querySelector('h3').textContent.toLowerCase();
                const cardDate = card.querySelector('.detail .value').textContent;
                
                const matchesDestination = !destination || cardDestination.includes(destination.toLowerCase());
                const matchesDate = !departureDate || cardDate.includes(departureDate);
                
                card.style.display = matchesDestination && matchesDate ? 'block' : 'none';
            });
        });
    }

    // Add smooth scrolling to all links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            document.querySelector(this.getAttribute('href')).scrollIntoView({
                behavior: 'smooth'
            });
        });
    });

    // Add animation to flight cards on scroll
    const observerOptions = {
        threshold: 0.1
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    document.querySelectorAll('.flight-card').forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        observer.observe(card);
    });
}); 