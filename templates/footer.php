    </div> 
    <footer class="footer mt-auto py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="d-flex align-items-center mb-3">
                        <i class="fas fa-theater-masks text-primary me-2 fa-2x"></i>
                        <div>
                            <h4 class="mb-0">STBS</h4>
                            <span class="text-muted">Sakr Theatre</span>
                        </div>
                    </div>
                    <p class="text-light-50">Experience the magic of live performances and book your tickets for the best shows in town.</p>
                    <div class="social-icons">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="/index.php"><i class="fas fa-chevron-right me-2 small"></i>Home</a></li>
                        <li><a href="/pages/shows.php"><i class="fas fa-chevron-right me-2 small"></i>Shows</a></li>
                        <li><a href="/pages/about.php"><i class="fas fa-chevron-right me-2 small"></i>About Us</a></li>
                        <li><a href="https://wa.me/+96103867749"><i class="fas fa-chevron-right me-2 small"></i>Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact Us</h5>
                    <address class="text-light-50">
                        <div class="d-flex mb-3">
                            <div class="me-3 text-primary"><i class="fas fa-map-marker-alt"></i></div>
                            <div>Beiruit Hazmieh</div>
                        </div>
                        <div class="d-flex mb-3">
                            <div class="me-3 text-primary"><i class="fas fa-phone"></i></div>
                            <div>+961 03 867 749</div>
                        </div>
                        <div class="d-flex">
                            <div class="me-3 text-primary"><i class="fas fa-envelope"></i></div>
                            <div>info@theatre.com</div>
                        </div>
                    </address>
                </div>
            </div>
            <hr class="mt-4 mb-4" style="border-color: rgba(255,255,255,0.1);">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="copyright mb-md-0">&copy; <?php echo date('Y'); ?> Sakr Theatre Booking System. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <ul class="list-inline mb-0">
                        <li class="list-inline-item"><a href="#" class="text-light-50 small">Privacy Policy</a></li>
                        <li class="list-inline-item"><a href="#" class="text-light-50 small">Terms of Use</a></li>
                        <li class="list-inline-item"><a href="#" class="text-light-50 small">FAQ</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <?php 
    if(function_exists('isLoggedIn') && isLoggedIn()) {
        include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/chat_include.php';
    }
    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>
    AOS.init();
</script>

</body>
</html> 