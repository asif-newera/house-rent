<?php
$pageTitle = 'Page Not Found';
require_once 'config/config.php';
require_once 'includes/header.php';
?>

<div class="error-page">
    <div class="container">
        <div class="error-content">
            <div class="error-code">404</div>
            <h1>Page Not Found</h1>
            <p>Sorry, the page you are looking for doesn't exist or has been moved.</p>
            <a href="/HOUSE%20RENT/" class="btn btn-primary">Back to Home</a>
        </div>
    </div>
</div>

<style>
.error-page {
    min-height: 80vh;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 60px 0;
}

.error-content {
    max-width: 600px;
    margin: 0 auto;
}

.error-code {
    font-size: 8rem;
    font-weight: 700;
    color: #4A6BFF;
    line-height: 1;
    margin-bottom: 20px;
}

.error-page h1 {
    font-size: 2.5rem;
    margin-bottom: 20px;
    color: #333;
}

.error-page p {
    font-size: 1.1rem;
    color: #666;
    margin-bottom: 30px;
}

@media (max-width: 768px) {
    .error-code {
        font-size: 6rem;
    }
    
    .error-page h1 {
        font-size: 2rem;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>