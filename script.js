   document.addEventListener('DOMContentLoaded', function() {
        // Handle stat items hover state
        const statItems = document.querySelectorAll('.stat-item');
        statItems[0].classList.add('active');
        
        statItems.forEach(item => {
            item.addEventListener('mouseover', function() {
                statItems.forEach(i => i.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // Scroll animation functionality
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    // Uncomment if you want animations to only happen once
                    // observer.unobserve(entry.target);
                } else {
                    // Optional: remove this if you want animations to stay after they've appeared
                    // entry.target.classList.remove('visible');
                }
            });
        }, observerOptions);

        // Observe all reveal elements
        document.querySelectorAll('.reveal').forEach(el => {
            observer.observe(el);
        });
    });