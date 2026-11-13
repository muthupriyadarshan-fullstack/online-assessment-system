<?php
require_once 'config/database.php';
require_once 'config/session.php';

$db = new Database();
requireUser();

$user = getCurrentUser();

// Get user statistics
$stats = $db->fetchOne("
    SELECT 
        COUNT(DISTINCT ua.assessment_id) as total_assessments,
        COUNT(ua.id) as total_attempts,
        AVG(ua.score) as average_score,
        MAX(ua.score) as best_score
    FROM user_attempts ua 
    WHERE ua.user_id = ? AND ua.is_completed = 1
", [$user['id']]);

// Get recent attempts
$recent_attempts = $db->fetchAll("
    SELECT ua.*, a.title, a.total_questions
    FROM user_attempts ua
    JOIN assessments a ON ua.assessment_id = a.id
    WHERE ua.user_id = ? AND ua.is_completed = 1
    ORDER BY ua.completed_at DESC
    LIMIT 5
", [$user['id']]);

// Get available assessments
$available_assessments = $db->fetchAll("
    SELECT a.*, 
           (SELECT COUNT(*) FROM user_attempts ua WHERE ua.user_id = ? AND ua.assessment_id = a.id AND ua.is_completed = 1) as attempts_count
    FROM assessments a 
    WHERE a.is_active = 1
    ORDER BY a.created_at DESC
", [$user['id']]);

// Get score distribution for charts
$score_distribution = $db->fetchAll("
    SELECT 
        CASE 
            WHEN ua.score >= 90 THEN 'A (90-100)'
            WHEN ua.score >= 80 THEN 'B (80-89)'
            WHEN ua.score >= 70 THEN 'C (70-79)'
            WHEN ua.score >= 60 THEN 'D (60-69)'
            ELSE 'F (Below 60)'
        END as grade_range,
        COUNT(*) as count
    FROM user_attempts ua
    WHERE ua.user_id = ? AND ua.is_completed = 1
    GROUP BY grade_range
    ORDER BY grade_range
", [$user['id']]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Assessment System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="user_dashboard.php">
                <i class="fas fa-graduation-cap me-2"></i>
                Assessment System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="user_dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="assessments.php">
                            <i class="fas fa-list me-1"></i>Assessments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="my_results.php">
                            <i class="fas fa-chart-line me-1"></i>My Results
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($user['full_name']); ?>
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
                            <i class="fas fa-user-circle me-2 text-purple"></i>
                            Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!
                        </h2>
                        <p class="card-text text-muted">
                            Track your assessment progress and view your performance analytics.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['total_assessments'] ?? 0; ?></div>
                    <div class="stats-label">Assessments Taken</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['total_attempts'] ?? 0; ?></div>
                    <div class="stats-label">Total Attempts</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo number_format($stats['average_score'] ?? 0, 1); ?>%</div>
                    <div class="stats-label">Average Score</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['best_score'] ?? 0; ?>%</div>
                    <div class="stats-label">Best Score</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Available Assessments -->
            <div class="col-lg-8 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>Available Assessments
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($available_assessments)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No assessments available</h5>
                                <p class="text-muted">Check back later for new assessments.</p>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($available_assessments as $assessment): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="assessment-card">
                                            <div class="assessment-title"><?php echo htmlspecialchars($assessment['title']); ?></div>
                                            <div class="assessment-meta">
                                                <div class="meta-item">
                                                    <i class="fas fa-question-circle"></i>
                                                    <?php echo $assessment['total_questions']; ?> Questions
                                                </div>
                                                <div class="meta-item">
                                                    <i class="fas fa-clock"></i>
                                                    <?php echo $assessment['time_limit']; ?> min
                                                </div>
                                                <div class="meta-item">
                                                    <i class="fas fa-history"></i>
                                                    <?php echo $assessment['attempts_count']; ?> Attempts
                                                </div>
                                            </div>
                                            <p class="text-muted mb-3"><?php echo htmlspecialchars($assessment['description']); ?></p>
                                            <a href="take_assessment.php?id=<?php echo $assessment['id']; ?>" 
                                               class="btn btn-primary">
                                                <i class="fas fa-play me-1"></i>Start Assessment
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Results & Charts -->
            <div class="col-lg-4">
                <!-- Recent Results -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-history me-2"></i>Recent Results
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_attempts)): ?>
                            <div class="text-center py-3">
                                <i class="fas fa-chart-line fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">No completed assessments yet</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_attempts as $attempt): ?>
                                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($attempt['title']); ?></h6>
                                        <small class="text-muted">
                                            <?php echo date('M j, Y', strtotime($attempt['completed_at'])); ?>
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-<?php echo $attempt['score'] >= 70 ? 'success' : ($attempt['score'] >= 50 ? 'warning' : 'danger'); ?>">
                                            <?php echo $attempt['score']; ?>%
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="text-center">
                                <a href="my_results.php" class="btn btn-outline-primary btn-sm">
                                    View All Results
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Score Distribution Chart -->
                <?php if (!empty($score_distribution)): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-pie me-2"></i>Score Distribution
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="scoreChart" width="300" height="300"></canvas>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        <?php if (!empty($score_distribution)): ?>
        // Score Distribution Chart
        const scoreData = <?php echo json_encode($score_distribution); ?>;
        const ctx = document.getElementById('scoreChart').getContext('2d');
        
        const labels = scoreData.map(item => item.grade_range);
        const data = scoreData.map(item => item.count);
        
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: [
                        '#6B46C1',
                        '#8B5CF6',
                        '#A78BFA',
                        '#C4B5FD',
                        '#E0E7FF'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            usePointStyle: true,
                            font: {
                                size: 12
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
