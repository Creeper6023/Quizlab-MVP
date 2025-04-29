    </main>
    
    <footer class="footer mt-auto py-3 bg-light border-top">
        <div class="container text-center">
        </div>
    </footer>
    
    <script src="<?= BASE_URL ?>/assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
    
    <?php if (isLoggedIn()): ?>
    <?php endif; ?>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var rows = document.querySelectorAll('[data-href]');
        rows.forEach(function(row) {
            row.addEventListener('click', function() {
                window.location.href = this.getAttribute('data-href');
            });
        });
        
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
        
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
