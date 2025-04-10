    </main>
    
    <footer class="footer mt-auto py-3 bg-light border-top">
        <div class="container text-center">
        </div>
    </footer>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="<?= BASE_URL ?>/assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
    
    <?php if (isLoggedIn()): ?>
    <!-- Basic user data available in PHP, no need for JavaScript -->
    <?php endif; ?>
    
    <script>
    // Minimal JavaScript for basic functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Make table rows clickable
        var rows = document.querySelectorAll('[data-href]');
        rows.forEach(function(row) {
            row.addEventListener('click', function() {
                window.location.href = this.getAttribute('data-href');
            });
        });
        
        // Initialize all Bootstrap tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Initialize all Bootstrap popovers
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
        
        // Initialize modals if they exist
        var modalElements = document.querySelectorAll('.modal');
        if (modalElements.length > 0) {
            modalElements.forEach(function(modalElement) {
                new bootstrap.Modal(modalElement);
            });
        }
    });
    </script>
</body>
</html>
