<?php
require_once 'config/database.php';
require_once 'config/session.php';

$db = new Database();
requireAdmin();

// Get comprehensive statistics
$stats = $db->fetchOne("
    SELECT 
        (SELECT COUNT(*) FROM users WHERE role = 'user') as total_users,
        (SELECT COUNT(*) FROM assessments) as total_assessments,
        (SELECT COUNT(*) FROM questions) as total_questions,
        (SELECT COUNT(*) FROM user_attempts WHERE is_completed = 1) as total_attempts,
        (SELECT AVG(score) FROM user_attempts WHERE is_completed = 1) as average_score,
        (SELECT MAX(score) FROM user_attempts WHERE is_completed = 1) as best_score,
        (SELECT MIN(score) FROM user_attempts WHERE is_completed = 1) as worst_score
");

// Get assessment performance
$assessment_performance = $db->fetchAll("
    SELECT 
        a.title,
        a.description,
        COUNT(ua.id) as attempts,
        AVG(ua.score) as avg_score,
        MAX(ua.score) as best_score,
        MIN(ua.score) as worst_score,
        COUNT(DISTINCT ua.user_id) as unique_users
    FROM assessments a
    LEFT JOIN user_attempts ua ON a.id = ua.assessment_id AND ua.is_completed = 1
    GROUP BY a.id, a.title, a.description
    ORDER BY attempts DESC
");

// Get user performance
$user_performance = $db->fetchAll("
    SELECT 
        u.full_name,
        u.username,
        COUNT(ua.id) as attempts,
        AVG(ua.score) as avg_score,
        MAX(ua.score) as best_score,
        MIN(ua.score) as worst_score
    FROM users u
    LEFT JOIN user_attempts ua ON u.id = ua.user_id AND ua.is_completed = 1
    WHERE u.role = 'user'
    GROUP BY u.id, u.full_name, u.username
    HAVING attempts > 0
    ORDER BY avg_score DESC
    LIMIT 20
");

// Get daily activity for last 30 days
$daily_activity = $db->fetchAll("
    SELECT 
        DATE(ua.completed_at) as date,
        COUNT(*) as attempts,
        COUNT(DISTINCT ua.user_id) as unique_users,
        AVG(ua.score) as avg_score
    FROM user_attempts ua
    WHERE ua.completed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    AND ua.is_completed = 1
    GROUP BY DATE(ua.completed_at)
    ORDER BY date DESC
");

// Handle export requests
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    
    if ($export_type == 'users') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="users_report.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Username', 'Full Name', 'Email', 'Role', 'Total Attempts', 'Average Score', 'Best Score', 'Joined Date']);
        
        $users = $db->fetchAll("
            SELECT u.*, 
                   (SELECT COUNT(*) FROM user_attempts ua WHERE ua.user_id = u.id AND ua.is_completed = 1) as total_attempts,
                   (SELECT AVG(ua.score) FROM user_attempts ua WHERE ua.user_id = u.id AND ua.is_completed = 1) as average_score,
                   (SELECT MAX(ua.score) FROM user_attempts ua WHERE ua.user_id = u.id AND ua.is_completed = 1) as best_score
            FROM users u
            ORDER BY u.created_at DESC
        ");
        
        foreach ($users as $user) {
            fputcsv($output, [
                $user['id'],
                $user['username'],
                $user['full_name'],
                $user['email'],
                $user['role'],
                $user['total_attempts'],
                number_format($user['average_score'] ?? 0, 2),
                $user['best_score'] ?? 0,
                $user['created_at']
            ]);
        }
        fclose($output);
        exit();
    }
    
    elseif ($export_type == 'assessments') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="assessments_report.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Title', 'Description', 'Questions', 'Time Limit', 'Attempts', 'Unique Users', 'Average Score', 'Best Score', 'Created Date']);
        
        foreach ($assessment_performance as $assessment) {
            fputcsv($output, [
                $assessment['id'] ?? '',
                $assessment['title'],
                $assessment['description'],
                $assessment['questions'] ?? 0,
                $assessment['time_limit'] ?? 0,
                $assessment['attempts'],
                $assessment['unique_users'],
                number_format($assessment['avg_score'] ?? 0, 2),
                $assessment['best_score'] ?? 0,
                $assessment['created_at'] ?? ''
            ]);
        }
        fclose($output);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Admin</title>
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
                        <a class="nav-link" href="admin_dashboard.php">
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
                        <a class="nav-link active" href="admin_reports.php">
                            <i class="fas fa-chart-bar me-1"></i>Reports
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Content -->
    <div class="container py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title mb-3">
                            <i class="fas fa-chart-bar me-2 text-purple"></i>
                            Reports & Analytics
                        </h2>
                        <p class="card-text text-muted">
                            Comprehensive analytics and export options for your assessment system.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Export Options -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-download me-2"></i>Export Data
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="?export=users" class="btn btn-outline-primary">
                                <i class="fas fa-users me-2"></i>Export Users
                            </a>
                            <a href="?export=assessments" class="btn btn-outline-primary">
                                <i class="fas fa-clipboard-list me-2"></i>Export Assessments
                            </a>
                            <button class="btn btn-outline-success" onclick="window.print()">
                                <i class="fas fa-print me-2"></i>Print Report
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Key Statistics -->
        <div class="row mb-4">
            <div class="col-md-2 col-sm-6 mb-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['total_users']; ?></div>
                    <div class="stats-label">Total Users</div>
                </div>
            </div>
            <div class="col-md-2 col-sm-6 mb-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['total_assessments']; ?></div>
                    <div class="stats-label">Assessments</div>
                </div>
            </div>
            <div class="col-md-2 col-sm-6 mb-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['total_questions']; ?></div>
                    <div class="stats-label">Questions</div>
                </div>
            </div>
            <div class="col-md-2 col-sm-6 mb-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['total_attempts']; ?></div>
                    <div class="stats-label">Attempts</div>
                </div>
            </div>
            <div class="col-md-2 col-sm-6 mb-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo number_format($stats['average_score'], 1); ?>%</div>
                    <div class="stats-label">Avg Score</div>
                </div>
            </div>
            <div class="col-md-2 col-sm-6 mb-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['best_score']; ?>%</div>
                    <div class="stats-label">Best Score</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Assessment Performance -->
            <div class="col-lg-8 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-line me-2"></i>Assessment Performance
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Assessment</th>
                                        <th>Attempts</th>
                                        <th>Unique Users</th>
                                        <th>Avg Score</th>
                                        <th>Best Score</th>
                                        <th>Worst Score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($assessment_performance as $assessment): ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($assessment['title']); ?></strong>
                                                    <?php if ($assessment['description']): ?>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($assessment['description']); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td><?php echo $assessment['attempts']; ?></td>
                                            <td><?php echo $assessment['unique_users']; ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $assessment['avg_score'] >= 70 ? 'success' : ($assessment['avg_score'] >= 50 ? 'warning' : 'danger'); ?>">
                                                    <?php echo number_format($assessment['avg_score'], 1); ?>%
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-success"><?php echo $assessment['best_score']; ?>%</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-danger"><?php echo $assessment['worst_score']; ?>%</span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Performers -->
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-trophy me-2"></i>Top Performers
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($user_performance)): ?>
                            <div class="text-center py-3">
                                <i class="fas fa-trophy fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">No performance data yet</p>
                            </div>
                        <?php else: ?>
                            <?php foreach (array_slice($user_performance, 0, 10) as $index => $user): ?>
                                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                    <div>
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-<?php echo $index < 3 ? 'warning' : 'secondary'; ?> me-2">
                                                #<?php echo $index + 1; ?>
                                            </span>
                                            <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                                        </div>
                                        <small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></small>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold text-purple"><?php echo number_format($user['avg_score'], 1); ?>%</div>
                                        <small class="text-muted"><?php echo $user['attempts']; ?> attempts</small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Activity Chart -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-area me-2"></i>Daily Activity (Last 30 Days)
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
        // Daily Activity Chart
        const activityData = <?php echo json_encode($daily_activity); ?>;
        const ctx = document.getElementById('activityChart').getContext('2d');
        
        // Prepare data for chart
        const labels = activityData.map(item => {
            const date = new Date(item.date);
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        }).reverse();
        
        const attemptsData = activityData.map(item => item.attempts).reverse();
        const usersData = activityData.map(item => item.unique_users).reverse();
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Assessment Attempts',
                    data: attemptsData,
                    borderColor: '#6B46C1',
                    backgroundColor: 'rgba(107, 70, 193, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    yAxisID: 'y'
                }, {
                    label: 'Unique Users',
                    data: usersData,
                    borderColor: '#8B5CF6',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    borderWidth: 3,
                    fill: false,
                    tension: 0.4,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Attempts'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Unique Users'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                }
            }
        });
    </script>
</body>
</html>
