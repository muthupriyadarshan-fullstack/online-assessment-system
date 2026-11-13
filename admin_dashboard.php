<?php
require_once 'config/database.php';
require_once 'config/session.php';

$db = new Database();
requireAdmin();

$admin = getCurrentUser();

// Get statistics
$stats = $db->fetchOne("
    SELECT 
        (SELECT COUNT(*) FROM users WHERE role = 'user') as total_users,
        (SELECT COUNT(*) FROM assessments) as total_assessments,
        (SELECT COUNT(*) FROM questions) as total_questions,
        (SELECT COUNT(*) FROM user_attempts WHERE is_completed = 1) as total_attempts,
        (SELECT AVG(score) FROM user_attempts WHERE is_completed = 1) as average_score
");

// Get recent user attempts
$recent_attempts = $db->fetchAll("
    SELECT ua.*, u.full_name, u.username, a.title
    FROM user_attempts ua
    JOIN users u ON ua.user_id = u.id
    JOIN assessments a ON ua.assessment_id = a.id
    WHERE ua.is_completed = 1
    ORDER BY ua.completed_at DESC
    LIMIT 10
");

// Get assessment performance
$assessment_performance = $db->fetchAll("
    SELECT 
        a.title,
        COUNT(ua.id) as attempts,
        AVG(ua.score) as avg_score,
        MAX(ua.score) as best_score,
        MIN(ua.score) as worst_score
    FROM assessments a
    LEFT JOIN user_attempts ua ON a.id = ua.assessment_id AND ua.is_completed = 1
    GROUP BY a.id, a.title
    ORDER BY attempts DESC
");

// Get user activity (last 30 days)
$user_activity = $db->fetchAll("
    SELECT 
        DATE(ua.completed_at) as date,
        COUNT(*) as attempts
    FROM user_attempts ua
    WHERE ua.completed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    AND ua.is_completed = 1
    GROUP BY DATE(ua.completed_at)
    ORDER BY date DESC
    LIMIT 30
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Assessment System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="admin_dashboard.php">
                <i class="fas fa-graduation-cap me-2"></i>
                Assessment System - Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="admin_dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_assessments.php">
                            <i class="fas fa-clipboard-list me-1"></i>Assessments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_questions.php">
                            <i class="fas fa-question-circle me-1"></i>Questions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_users.php">
                            <i class="fas fa-users me-1"></i>Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_reports.php">
                            <i class="fas fa-chart-bar me-1"></i>Reports
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-shield me-1"></i><?php echo htmlspecialchars($admin['full_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-edit me-2"></i>Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Dashboard Content -->
    <div class="container py-4">
        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title mb-3">
                            <i class="fas fa-user-shield me-2 text-purple"></i>
                            Welcome, <?php echo htmlspecialchars($admin['full_name']); ?>!
                        </h2>
                        <p class="card-text text-muted">
                            Manage assessments, monitor user activity, and view comprehensive analytics.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-2 col-sm-6 mb-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['total_users'] ?? 0; ?></div>
                    <div class="stats-label">Total Users</div>
                </div>
            </div>
            <div class="col-md-2 col-sm-6 mb-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['total_assessments'] ?? 0; ?></div>
                    <div class="stats-label">Assessments</div>
                </div>
            </div>
            <div class="col-md-2 col-sm-6 mb-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['total_questions'] ?? 0; ?></div>
                    <div class="stats-label">Questions</div>
                </div>
            </div>
            <div class="col-md-2 col-sm-6 mb-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['total_attempts'] ?? 0; ?></div>
                    <div class="stats-label">Attempts</div>
                </div>
            </div>
            <div class="col-md-2 col-sm-6 mb-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo number_format($stats['average_score'] ?? 0, 1); ?>%</div>
                    <div class="stats-label">Avg Score</div>
                </div>
            </div>
            <div class="col-md-2 col-sm-6 mb-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo date('M j'); ?></div>
                    <div class="stats-label">Today</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Activity -->
            <div class="col-lg-8 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-history me-2"></i>Recent User Activity
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_attempts)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No recent activity</h5>
                                <p class="text-muted">User attempts will appear here once they start taking assessments.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Assessment</th>
                                            <th>Score</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_attempts as $attempt): ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($attempt['full_name']); ?></strong>
                                                        <br><small class="text-muted">@<?php echo htmlspecialchars($attempt['username']); ?></small>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($attempt['title']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $attempt['score'] >= 70 ? 'success' : ($attempt['score'] >= 50 ? 'warning' : 'danger'); ?>">
                                                        <?php echo $attempt['score']; ?>%
                                                    </span>
                                                </td>
                                                <td><?php echo date('M j, Y H:i', strtotime($attempt['completed_at'])); ?></td>
                                                <td>
                                                    <span class="badge bg-success">Completed</span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Actions & Assessment Performance -->
            <div class="col-lg-4">
                <!-- Quick Actions -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-bolt me-2"></i>Quick Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="admin_questions.php?action=add" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Add Questions
                            </a>
                            <a href="admin_assessments.php?action=create" class="btn btn-outline-primary">
                                <i class="fas fa-clipboard-list me-2"></i>Create Assessment
                            </a>
                            <a href="admin_users.php" class="btn btn-outline-primary">
                                <i class="fas fa-users me-2"></i>Manage Users
                            </a>
                            <a href="admin_reports.php" class="btn btn-outline-primary">
                                <i class="fas fa-download me-2"></i>Export Reports
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Assessment Performance -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-pie me-2"></i>Assessment Performance
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($assessment_performance)): ?>
                            <div class="text-center py-3">
                                <i class="fas fa-chart-pie fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">No assessment data yet</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($assessment_performance as $perf): ?>
                                <div class="mb-3 pb-3 border-bottom">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($perf['title']); ?></h6>
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <small class="text-muted">Attempts</small>
                                            <div class="fw-bold"><?php echo $perf['attempts']; ?></div>
                                        </div>
                                        <div class="col-4">
                                            <small class="text-muted">Avg Score</small>
                                            <div class="fw-bold"><?php echo number_format($perf['avg_score'] ?? 0, 1); ?>%</div>
                                        </div>
                                        <div class="col-4">
                                            <small class="text-muted">Best</small>
                                            <div class="fw-bold"><?php echo $perf['best_score'] ?? 0; ?>%</div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-line me-2"></i>User Activity (Last 30 Days)
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="activityChart" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // User Activity Chart
        const activityData = <?php echo json_encode($user_activity); ?>;
        const ctx = document.getElementById('activityChart').getContext('2d');
        
        // Prepare data for chart
        const labels = activityData.map(item => {
            const date = new Date(item.date);
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        }).reverse();
        
        const data = activityData.map(item => item.attempts).reverse();
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Assessment Attempts',
                    data: data,
                    borderColor: '#6B46C1',
                    backgroundColor: 'rgba(107, 70, 193, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#6B46C1',
                    pointBorderColor: '#6B46C1',
                    pointRadius: 6,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
