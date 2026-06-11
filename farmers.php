<?php

$page_title="Our Farmers - FarmMart";

require_once("includes/config.php");
require_once("includes/header.php");
require_once("includes/navbar.php");


// CURRENT USER

$current_user_id=$_SESSION['user_id'] ?? null;



// SEARCH

$search="";

if(isset($_GET['search']))
{
$search=trim($_GET['search']);
}



$query="

SELECT 
f.id,
f.user_id,
f.name,
f.village,
f.rating,
f.total_reviews,
f.status,

u.profile_picture,
u.id AS farmer_user_id,

COUNT(p.id) as total_products

FROM farmers f

LEFT JOIN users u 
ON f.user_id=u.id

LEFT JOIN products p
ON f.id=p.farmer_id 
AND p.status='active'

WHERE f.status='approved'
";

if($search!="")
{
$query.=" AND f.name LIKE '%$search%'";
}

$query.="

GROUP BY f.id

ORDER BY f.name ASC

";

$result=mysqli_query($conn,$query);

$farmers=mysqli_fetch_all($result,MYSQLI_ASSOC);

?>

<style>
/* Premium Farmers Page Styling */
:root {
  --theme-primary: #047857; /* Deep emerald green */
  --theme-secondary: #059669;
  --theme-dark: #111827;
  --theme-light: #f9fafb;
  --theme-accent: #f59e0b; /* Amber */
  --font-heading: 'Poppins', sans-serif;
  --font-body: 'Inter', sans-serif;
  --transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
}

body {
  background-color: var(--theme-light);
  font-family: var(--font-body);
  color: #4b5563;
}

h1, h2, h3, h4, h5, h6 {
  font-family: var(--font-heading);
  color: var(--theme-dark);
  font-weight: 700;
}

/* Farmers Hero Banner */
.farmers-hero {
    min-height: 45vh;
    background: linear-gradient(rgba(17, 24, 39, 0.8), rgba(17, 24, 39, 0.6)), 
                url('https://images.unsplash.com/photo-1595841696677-6479ff3f62eb?auto=format&fit=crop&w=1920&q=80') no-repeat center center;
    background-size: cover;
    background-attachment: fixed;
    position: relative;
    display: flex;
    align-items: center;
}

.hero-title {
    font-size: 55px;
    font-weight: 800;
    color: #fff;
    margin-bottom: 20px;
}
.hero-title span {
    color: #34d399; /* Light emerald accent */
}

/* Search Bar */
.search-wrapper {
    background: #fff;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.03);
    margin-top: -40px;
    position: relative;
    z-index: 10;
}

.search-input-group {
    background: var(--theme-light);
    border-radius: 50px;
    padding: 5px;
    display: flex;
    align-items: center;
    border: 1px solid #e5e7eb;
    transition: var(--transition);
}

.search-input-group:focus-within {
    border-color: var(--theme-primary);
    box-shadow: 0 0 0 4px rgba(4, 120, 87, 0.1);
    background: #fff;
}

.search-input-group input {
    background: transparent;
    border: none;
    padding: 12px 24px;
    font-size: 15px;
    outline: none;
    flex-grow: 1;
    color: var(--theme-dark);
}

.search-input-group button {
    background: var(--theme-primary);
    color: #fff;
    border: none;
    border-radius: 40px;
    padding: 10px 30px;
    font-weight: 600;
    transition: var(--transition);
}

.search-input-group button:hover {
    background: var(--theme-secondary);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(4, 120, 87, 0.2);
}

/* Premium Farmer Card */
.farmer-card {
    background: #fff;
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.03);
    transition: var(--transition);
    position: relative;
    height: 100%;
    border: 1px solid rgba(0,0,0,0.02);
    display: flex;
    flex-direction: column;
}

.farmer-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.08);
}

.card-header-bg {
    height: 100px;
    background: linear-gradient(135deg, rgba(4, 120, 87, 0.1) 0%, rgba(5, 150, 105, 0.2) 100%);
    position: relative;
}

.verified-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    background: #fff;
    color: var(--theme-primary);
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    display: flex;
    align-items: center;
    gap: 4px;
}

.farmer-avatar-wrapper {
    text-align: center;
    margin-top: -50px;
    position: relative;
    z-index: 2;
}

.farmer-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #fff;
    box-shadow: 0 8px 20px rgba(0,0,0,0.08);
    background: #fff;
}

.f-card-body {
    padding: 20px 25px 25px;
    text-align: center;
    display: flex;
    flex-direction: column;
    flex: 1;
}

.f-name {
    font-size: 20px;
    font-weight: 700;
    color: var(--theme-dark);
    margin-bottom: 5px;
}

.f-location {
    color: #6b7280;
    font-size: 13px;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
}

.f-stats-container {
    display: flex;
    justify-content: center;
    gap: 30px;
    margin-bottom: 20px;
    padding: 15px 0;
    border-top: 1px solid #f3f4f6;
    border-bottom: 1px solid #f3f4f6;
}

.f-stat-item {
    display: flex;
    flex-direction: column;
}

.f-stat-val {
    font-size: 18px;
    font-weight: 800;
    color: var(--theme-primary);
}

.f-stat-label {
    font-size: 11px;
    text-transform: uppercase;
    color: #9ca3af;
    letter-spacing: 0.5px;
    font-weight: 600;
}

.f-rating {
    color: var(--theme-accent);
    font-size: 14px;
    margin-bottom: 20px;
}
.f-rating span {
    color: #9ca3af;
    font-size: 13px;
    margin-left: 5px;
}

.f-actions {
    margin-top: auto;
    display: grid;
    gap: 10px;
}

.btn-f-profile {
    background: transparent;
    color: var(--theme-primary);
    border: 2px solid var(--theme-primary);
    padding: 10px;
    border-radius: 12px;
    font-weight: 600;
    transition: var(--transition);
    text-decoration: none;
    display: block;
}
.btn-f-profile:hover {
    background: var(--theme-primary);
    color: #fff;
}

.btn-f-chat {
    background: var(--theme-primary);
    color: #fff;
    border: none;
    padding: 12px;
    border-radius: 12px;
    font-weight: 600;
    transition: var(--transition);
    text-decoration: none;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
}
.btn-f-chat:hover {
    background: var(--theme-secondary);
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 8px 15px rgba(4, 120, 87, 0.2);
}

.btn-f-disabled {
    background: #f3f4f6;
    color: #9ca3af;
    border: none;
    padding: 12px;
    border-radius: 12px;
    font-weight: 600;
    cursor: not-allowed;
    text-decoration: none;
    display: block;
}
</style>

<!-- Hero Banner -->
<section class="farmers-hero">
    <div class="container text-center">
        <div class="row justify-content-center">
            <div class="col-lg-8" data-aos="fade-up">
                <h1 class="hero-title">
                    Meet Our <br><span>Local Farmers</span>
                </h1>
                <p class="lead text-white opacity-75 mb-0" style="font-weight: 300;">
                    Connect directly with the hardworking individuals who grow your food. 
                    Support sustainable agriculture and your local community.
                </p>
            </div>
        </div>
    </div>
</section>

<main class="container pb-5">

    <!-- Search Section -->
    <div class="row mb-5 justify-content-center">
        <div class="col-xl-8 col-lg-10">
            <div class="search-wrapper" data-aos="fade-up">
                <form action="farmers.php" method="GET">
                    <div class="search-input-group">
                        <i class="bi bi-search ms-3 text-muted"></i>
                        <input type="text" name="search" placeholder="Search for a farmer by name..." value="<?= htmlspecialchars($search) ?>">
                        <button type="submit">Find Farmer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Farmers Grid -->
    <div class="row g-4">

        <?php if(count($farmers)>0): ?>
            <?php foreach($farmers as $index => $farmer): ?>
                <div class="col-xl-3 col-lg-4 col-sm-6" data-aos="fade-up" data-aos-delay="<?= ($index % 8) * 50 ?>">
                    <div class="farmer-card">
                        
                        <div class="card-header-bg">
                            <?php if($farmer['status']=="approved"): ?>
                                <span class="verified-badge">
                                    <i class="bi bi-patch-check-fill"></i> Verified
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="farmer-avatar-wrapper">
                            <img src="<?= $farmer['profile_picture'] ? BASE_URL.'uploads/users/'.htmlspecialchars($farmer['profile_picture']) : BASE_URL.'assets/default-user.png' ?>" class="farmer-avatar" alt="<?= htmlspecialchars($farmer['name']) ?>">
                        </div>

                        <div class="f-card-body">
                            <h5 class="f-name"><?= htmlspecialchars($farmer['name']) ?></h5>
                            <div class="f-location">
                                <i class="bi bi-geo-alt-fill text-success opacity-75"></i>
                                <?= htmlspecialchars($farmer['village']) ?>
                            </div>

                            <div class="f-rating">
                                <i class="bi bi-star-fill"></i>
                                <strong><?= number_format($farmer['rating'], 1) ?></strong>
                                <span>(<?= $farmer['total_reviews'] ?? 0 ?> Reviews)</span>
                            </div>

                            <div class="f-stats-container">
                                <div class="f-stat-item">
                                    <span class="f-stat-val"><?= $farmer['total_products'] ?></span>
                                    <span class="f-stat-label">Products</span>
                                </div>
                                <div class="f-stat-item">
                                    <span class="f-stat-val">100%</span>
                                    <span class="f-stat-label">Organic</span>
                                </div>
                            </div>

                            <div class="f-actions">
                                <a href="farmer_profile.php?id=<?= $farmer['id'] ?>" class="btn-f-profile">
                                    View Store
                                </a>

                                <?php if($current_user_id): ?>
                                    <?php if($current_user_id != $farmer['farmer_user_id']): ?>
                                        <a href="chat/chat.php?user=<?= $farmer['farmer_user_id'] ?>" class="btn-f-chat">
                                            <i class="bi bi-chat-dots"></i> Message
                                        </a>
                                    <?php else: ?>
                                        <div class="btn-f-disabled">
                                            This is you
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <a href="login.php" class="btn-f-chat bg-secondary text-white" style="box-shadow: none;">
                                        <i class="bi bi-box-arrow-in-right"></i> Login to Chat
                                    </a>
                                <?php endif; ?>
                            </div>

                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <i class="bi bi-people text-muted" style="font-size: 4rem; opacity: 0.3;"></i>
                <h5 class="mt-3 text-muted">No farmers found matching your search.</h5>
                <a href="farmers.php" class="btn btn-outline-success mt-3 rounded-pill px-4">View All Farmers</a>
            </div>
        <?php endif; ?>

    </div>
</main>

<?php require_once("includes/footer.php"); ?>