<?php
/**
 * Database Migration: Add booking_date column to bookings table
 * Access via browser: http://localhost/HOUSE%20RENT/migrate_bookings.php
 */

require_once __DIR__ . '/config/config.php';

// Simple security: require confirmation parameter
$confirmed = isset($_GET['confirm']) && $_GET['confirm'] === 'yes';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migration - Bookings Table</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Database Migration: Bookings Table</h4>
                    </div>
                    <div class="card-body">
                        <?php if (!$confirmed): ?>
                            <div class="alert alert-warning">
                                <h5><i class="bi bi-exclamation-triangle"></i> Migration Required</h5>
                                <p>This will add the missing <code>booking_date</code> column to the <code>bookings</code> table.</p>
                                <p class="mb-0"><strong>This is safe and will not affect existing data.</strong></p>
                            </div>
                            <div class="d-grid gap-2">
                                <a href="?confirm=yes" class="btn btn-primary btn-lg">
                                    Run Migration
                                </a>
                                <a href="admin/dashboard.php" class="btn btn-secondary">
                                    Cancel
                                </a>
                            </div>
                        <?php else:
                            try {
                                // Check if booking_date column already exists
                                $checkQuery = "SHOW COLUMNS FROM bookings LIKE 'booking_date'";
                                $result = $pdo->query($checkQuery);
                                
                                if ($result->rowCount() > 0) {
                                    echo '<div class="alert alert-info">';
                                    echo '<h5><i class="bi bi-info-circle"></i> Already Up to Date</h5>';
                                    echo '<p>Column <code>booking_date</code> already exists. No migration needed.</p>';
                                    echo '</div>';
                                } else {
                                    // Add the booking_date column
                                    $alterQuery = "ALTER TABLE bookings 
                                                   ADD COLUMN booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER status";
                                    $pdo->exec($alterQuery);
                                    
                                    echo '<div class="alert alert-success">';
                                    echo '<h5><i class="bi bi-check-circle"></i> Migration Successful!</h5>';
                                    echo '<p>Successfully added <code>booking_date</code> column to the bookings table.</p>';
                                    echo '</div>';
                                }
                                
                                // Display current table structure
                                echo '<h5 class="mt-4">Current Table Structure:</h5>';
                                echo '<div class="table-responsive">';
                                echo '<table class="table table-bordered table-sm">';
                                echo '<thead class="table-light">';
                                echo '<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>';
                                echo '</thead><tbody>';
                                
                                $describeQuery = "DESCRIBE bookings";
                                $columns = $pdo->query($describeQuery)->fetchAll(PDO::FETCH_ASSOC);
                                
                                foreach ($columns as $column) {
                                    echo '<tr>';
                                    echo '<td><code>' . htmlspecialchars($column['Field']) . '</code></td>';
                                    echo '<td>' . htmlspecialchars($column['Type']) . '</td>';
                                    echo '<td>' . htmlspecialchars($column['Null']) . '</td>';
                                    echo '<td>' . htmlspecialchars($column['Key']) . '</td>';
                                    echo '<td>' . htmlspecialchars($column['Default'] ?? 'NULL') . '</td>';
                                    echo '</tr>';
                                }
                                
                                echo '</tbody></table></div>';
                                
                                echo '<div class="d-grid gap-2 mt-4">';
                                echo '<a href="admin/bookings.php" class="btn btn-success">Go to Bookings Management</a>';
                                echo '<a href="admin/dashboard.php" class="btn btn-secondary">Go to Dashboard</a>';
                                echo '</div>';
                                
                            } catch (PDOException $e) {
                                echo '<div class="alert alert-danger">';
                                echo '<h5><i class="bi bi-x-circle"></i> Migration Failed</h5>';
                                echo '<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
                                echo '</div>';
                            }
                        endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
