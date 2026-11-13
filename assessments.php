<?php
require_once 'config/database.php';
require_once 'config/session.php';

$db = new Database();
requireUser();

$user_id = $_SESSION['user_id'];

// Get available assessments with user's attempt history
$assessments = $db->fetchAll("
    SELECT a.*, 
           (SELECT COUNT(*) FROM user_attempts ua WHERE ua.user_id = ? AND ua.assessment_id = a.id AND ua.is_completed = 1) as attempts_count,
           (SELECT MAX(ua.score) FROM user_attempts ua WHERE ua.user_id = ? AND ua.assessment_id = a.id AND ua.is_completed = 1) as best_score
    FROM assessments a 
    WHERE a.is_active = 1
    ORDER BY a.created_at DESC
", [$user_id, $user_id]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Assessments - Assessment System</title>
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
                        <a class="nav-link active" href="assessments.php">
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
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['full_name']); ?>
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
                            <i class="fas fa-list me-2 text-purple"></i>
                            Available Assessments
                        </h2>
                        <p class="card-text text-muted">
                            Choose an assessment to test your knowledge and track your progress.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assessments Grid -->
        <div class="row">
            <?php if (empty($assessments)): ?>
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No assessments available</h5>
                            <p class="text-muted">Check back later for new assessments.</p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($assessments as $assessment): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0"><?php echo htmlspecialchars($assessment['title']); ?></h5>
                            </div>
                            <div class="card-body">
                                <p class="card-text text-muted mb-3">
                                    <?php echo htmlspecialchars($assessment['description']); ?>
                                </p>
                                
                                <div class="row text-center mb-3">
                                    <div class="col-4">
                                        <div class="fw-bold text-purple"><?php echo $assessment['total_questions']; ?></div>
                                        <small class="text-muted">Questions</small>
                                    </div>
                                    <div class="col-4">
                                        <div class="fw-bold text-purple"><?php echo $assessment['time_limit']; ?></div>
                                        <small class="text-muted">Minutes</small>
                                    </div>
                                    <div class="col-4">
                                        <div class="fw-bold text-purple"><?php echo $assessment['attempts_count']; ?></div>
                                        <small class="text-muted">Attempts</small>
                                    </div>
                                </div>

                                <?php if ($assessment['best_score']): ?>
                                    <div class="alert alert-info mb-3">
                                        <i class="fas fa-trophy me-2"></i>
                                        <strong>Best Score:</strong> <?php echo $assessment['best_score']; ?>%
                                    </div>
                                <?php endif; ?>
                                
                                <div class="d-grid">
                                    <a href="take_assessment.php?id=<?php echo $assessment['id']; ?>" 
                                       class="btn btn-primary btn-lg">
                                        <i class="fas fa-play me-2"></i>
                                        <?php echo $assessment['attempts_count'] > 0 ? 'Retake Assessment' : 'Start Assessment'; ?>
                                    </a>
                                </div>
                            </div>
                            <div class="card-footer text-muted">
                                <small>
                                    <i class="fas fa-calendar me-1"></i>
                                    Available since <?php echo date('M j, Y', strtotime($assessment['created_at'])); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
