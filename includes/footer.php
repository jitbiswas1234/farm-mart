<footer class="bg-dark text-light py-5 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <h4 class="text-success"><i class="bi bi-basket2-fill"></i> FarmMart</h4>
                <p>Connecting you directly with local farmers.<br>Fresh, organic, and traceable produce.</p>
            </div>
            <div class="col-md-2">
                <h6>Quick Links</h6>
                <ul class="list-unstyled">
                    <li><a href="#" class="text-light text-decoration-none">Home</a></li>
                    <li><a href="#" class="text-light text-decoration-none">All Products</a></li>
                    <li><a href="#" class="text-light text-decoration-none">Our Farmers</a></li>
                </ul>
            </div>
            <div class="col-md-2">
                <h6>Categories</h6>
                <ul class="list-unstyled">
                    <li><a href="#" class="text-light text-decoration-none">Vegetables</a></li>
                    <li><a href="#" class="text-light text-decoration-none">Fruits</a></li>
                    <li><a href="#" class="text-light text-decoration-none">Dairy</a></li>
                    <li><a href="#" class="text-light text-decoration-none">Organic</a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h6>Newsletter</h6>
                <p>Get weekly updates on fresh produce and seasonal offers.</p>
                <div class="input-group">
                    <input type="email" class="form-control" placeholder="Your email">
                    <button class="btn btn-success">Subscribe</button>
                </div>
            </div>
        </div>
        
        <hr class="my-4">
        <div class="text-center text-muted">
            &copy; <?= date("Y") ?> FarmMart - Local Farmers Market. All rights reserved.
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({
        duration: 800,
        once: true,
        offset: 100
    });
</script>
<script src="assets/js/main.js"></script>
</body>
</html>