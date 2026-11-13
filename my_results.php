<?php
require_once 'config/database.php';
require_once 'config/session.php';

$db = new Database();
requireUser();

$user = getCurrentUser();

// Get user's assessment results
$results = $db->fetchAll("
    SELECT ua.*, a.title, a.description, a.total_questions
    FROM user_attempts ua
    JOIN assessments a ON ua.assessment_id = a.id
    WHERE ua.user_id = ? AND ua.is_completed = 1
    ORDER BY ua.completed_at DESC
", [$user['id']]);

// Get performance statistics
$performance_stats = $db->fetchOne("
    SELECT 
        COUNT(*) as total_attempts,
        AVG(score) as average_score,
        MAX(score) as best_score,
        MIN(score) as worst_score,
        COUNT(DISTINCT assessment_id) as assessments_taken
    FROM user_attempts 
    WHERE user_id = ? AND is_completed = 1
", [$user['id']]);

// Get score distribution
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

// Get recent performance trend
$recent_trend = $db->fetchAll("
    SELECT 
        DATE(completed_at) as date,
        AVG(score) as avg_score,
        COUNT(*) as attempts
    FROM user_attempts 
    WHERE user_id = ? AND is_completed = 1 
    AND completed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(completed_at)
    ORDER BY date DESC
    LIMIT 10
", [$user['id']]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Results - Assessment System</title>
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
                        <a class="nav-link" href="user_dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="assessments.php">
                            <i class="fas fa-list me-1"></i>Assessments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="my_results.php">
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

    <!-- Content -->
    <div class="container py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title mb-3">
                            <i class="fas fa-chart-line me-2 text-purple"></i>
                            My Assessment Results
                        </h2>
                        <p class="card-text text-muted">
                            Track your performance and view detailed results from all your assessments.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Statistics -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $performance_stats['total_attempts']; ?></div>
                    <div class="stats-label">Total Attempts</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $performance_stats['assessments_taken']; ?></div>
                    <div class="stats-label">Assessments Taken</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo number_format($performance_stats['average_score'], 1); ?>%</div>
                    <div class="stats-label">Average Score</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $performance_stats['best_score']; ?>%</div>
                    <div class="stats-label">Best Score</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Results Table -->
            <div class="col-lg-8 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>All Results (<?php echo count($results); ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($results)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No results found</h5>
                                <p class="text-muted">Complete some assessments to see your results here.</p>
                                <a href="user_dashboard.php" class="btn btn-primary">
                                    <i class="fas fa-list me-2"></i>View Available Assessments
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Assessment</th>
                                            <th>Score</th>
                                            <th>Grade</th>
                                            <th>Correct</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($results as $result): ?>
                                            <?php
                                            $grade = 'F';
                                            if ($result['score'] >= 90) $grade = 'A';
                                            elseif ($result['score'] >= 80) $grade = 'B';
                                            elseif ($result['score'] >= 70) $grade = 'C';
                                            elseif ($result['score'] >= 60) $grade = 'D';
                                            ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($result['title']); ?></strong>
                                                        <?php if ($result['description']): ?>
                                                            <br><small class="text-muted"><?php echo htmlspecialchars($result['description']); ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $result['score'] >= 70 ? 'success' : ($result['score'] >= 50 ? 'warning' : 'danger'); ?> fs-6">
                                                        <?php echo $result['score']; ?>%
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $grade == 'A' ? 'success' : ($grade == 'B' ? 'info' : ($grade == 'C' ? 'warning' : 'danger')); ?>">
                                                        <?php echo $grade; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php echo $result['correct_answers']; ?>/<?php echo $result['total_questions']; ?>
                                                </td>
                                                <td><?php echo date('M j, Y H:i', strtotime($result['completed_at'])); ?></td>
                                                <td>
                                                    <a href="assessment_result.php?attempt_id=<?php echo $result['id']; ?>" 
                                                       class="btn btn-outline-primary btn-sm">
                                                        <i class="fas fa-eye me-1"></i>View Details
                                                    </a>
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

            <!-- Charts -->
            <div class="col-lg-4">
                <!-- Score Distribution -->
                <?php if (!empty($score_distribution)): ?>
                <div class="card mb-4">
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

                <!-- Performance Trend -->
                <?php if (!empty($recent_trend)): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-line me-2"></i>Recent Performance
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="trendChart" width="300" height="200"></canvas>
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
        const scoreCtx = document.getElementById('scoreChart').getContext('2d');
        
        const scoreLabels = scoreData.map(item => item.grade_range);
        const scoreValues = scoreData.map(item => item.count);
        
        new Chart(scoreCtx, {
            type: 'doughnut',
            data: {
                labels: scoreLabels,
                datasets: [{
                    data: scoreValues,
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

        <?php if (!empty($recent_trend)): ?>
        // Performance Trend Chart
        const trendData = <?php echo json_encode($recent_trend); ?>;
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        
        const trendLabels = trendData.map(item => {
            const date = new Date(item.date);
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        }).reverse();
        
        const trendScores = trendData.map(item => parseFloat(item.avg_score)).reverse();
        
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: trendLabels,
                datasets: [{
                    label: 'Average Score',
                    data: trendScores,
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
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
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
