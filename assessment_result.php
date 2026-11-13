<?php
require_once 'config/database.php';
require_once 'config/session.php';

$db = new Database();
requireUser();

$attempt_id = $_GET['attempt_id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$attempt_id) {
    header('Location: user_dashboard.php');
    exit();
}

// Get attempt details
$attempt = $db->fetchOne("
    SELECT ua.*, a.title, a.description, a.total_questions
    FROM user_attempts ua
    JOIN assessments a ON ua.assessment_id = a.id
    WHERE ua.id = ? AND ua.user_id = ?
", [$attempt_id, $user_id]);

if (!$attempt) {
    header('Location: user_dashboard.php');
    exit();
}

// Get detailed results
$results = $db->fetchAll("
    SELECT q.*, ua.selected_answer, ua.is_correct
    FROM user_answers ua
    JOIN questions q ON ua.question_id = q.id
    WHERE ua.attempt_id = ?
    ORDER BY q.id
", [$attempt_id]);

// Calculate grade
$grade = 'F';
if ($attempt['score'] >= 90) $grade = 'A';
elseif ($attempt['score'] >= 80) $grade = 'B';
elseif ($attempt['score'] >= 70) $grade = 'C';
elseif ($attempt['score'] >= 60) $grade = 'D';

// Get performance message
$performance_message = '';
if ($attempt['score'] >= 90) {
    $performance_message = 'Excellent work! You have mastered this assessment.';
} elseif ($attempt['score'] >= 80) {
    $performance_message = 'Great job! You have a strong understanding of the material.';
} elseif ($attempt['score'] >= 70) {
    $performance_message = 'Good work! Consider reviewing some areas for improvement.';
} elseif ($attempt['score'] >= 60) {
    $performance_message = 'You passed, but consider studying more before retaking.';
} else {
    $performance_message = 'Consider reviewing the material and trying again.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assessment Result - Assessment System</title>
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
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="user_dashboard.php">
                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                </a>
                <a class="nav-link" href="my_results.php">
                    <i class="fas fa-chart-line me-1"></i>All Results
                </a>
            </div>
        </div>
    </nav>

    <!-- Result Content -->
    <div class="container py-4">
        <!-- Result Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header text-center">
                        <h2 class="mb-3">
                            <i class="fas fa-trophy me-2"></i>
                            Assessment Completed!
                        </h2>
                        <h4 class="text-white-50"><?php echo htmlspecialchars($attempt['title']); ?></h4>
                    </div>
                    <div class="card-body text-center">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <div class="stats-card">
                                    <div class="stats-number text-<?php echo $attempt['score'] >= 70 ? 'success' : ($attempt['score'] >= 50 ? 'warning' : 'danger'); ?>">
                                        <?php echo $attempt['score']; ?>%
                                    </div>
                                    <div class="stats-label">Final Score</div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="stats-card">
                                    <div class="stats-number text-purple"><?php echo $grade; ?></div>
                                    <div class="stats-label">Grade</div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="stats-card">
                                    <div class="stats-number text-purple"><?php echo $attempt['correct_answers']; ?></div>
                                    <div class="stats-label">Correct Answers</div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="stats-card">
                                    <div class="stats-number text-purple"><?php echo $attempt['total_questions']; ?></div>
                                    <div class="stats-label">Total Questions</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <div class="alert alert-<?php echo $attempt['score'] >= 70 ? 'success' : ($attempt['score'] >= 50 ? 'warning' : 'danger'); ?>">
                                <i class="fas fa-<?php echo $attempt['score'] >= 70 ? 'check-circle' : ($attempt['score'] >= 50 ? 'exclamation-triangle' : 'times-circle'); ?> me-2"></i>
                                <?php echo $performance_message; ?>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-3 justify-content-center flex-wrap">
                            <a href="user_dashboard.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-tachometer-alt me-2"></i>Back to Dashboard
                            </a>
                            <a href="my_results.php" class="btn btn-outline-primary btn-lg">
                                <i class="fas fa-chart-line me-2"></i>View All Results
                            </a>
                            <button class="btn btn-outline-success btn-lg" onclick="window.print()">
                                <i class="fas fa-print me-2"></i>Print Result
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Results -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list-check me-2"></i>Detailed Results
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($results as $index => $result): ?>
                            <div class="question-card mb-4">
                                <div class="question-text">
                                    <span class="badge bg-<?php echo $result['is_correct'] ? 'success' : 'danger'; ?> me-2">
                                        <?php echo $index + 1; ?>
                                    </span>
                                    <?php echo htmlspecialchars($result['question_text']); ?>
                                </div>
                                
                                <div class="options">
                                    <?php 
                                    $options = [
                                        'A' => $result['option_a'],
                                        'B' => $result['option_b'],
                                        'C' => $result['option_c'],
                                        'D' => $result['option_d']
                                    ];
                                    
                                    foreach ($options as $letter => $text): 
                                        $is_selected = ($result['selected_answer'] === $letter);
                                        $is_correct = ($result['correct_answer'] === $letter);
                                        
                                        $class = 'option';
                                        if ($is_selected && $is_correct) {
                                            $class .= ' correct';
                                        } elseif ($is_selected && !$is_correct) {
                                            $class .= ' incorrect';
                                        } elseif ($is_correct) {
                                            $class .= ' correct';
                                        }
                                    ?>
                                        <div class="<?php echo $class; ?>">
                                            <div class="d-flex align-items-center">
                                                <strong class="me-2"><?php echo $letter; ?>.</strong>
                                                <span class="flex-grow-1"><?php echo htmlspecialchars($text); ?></span>
                                                <?php if ($is_selected): ?>
                                                    <i class="fas fa-check-circle text-success ms-2"></i>
                                                <?php endif; ?>
                                                <?php if ($is_correct && !$is_selected): ?>
                                                    <i class="fas fa-star text-warning ms-2"></i>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="mt-3">
                                    <small class="text-muted">
                                        <strong>Your Answer:</strong> 
                                        <span class="badge bg-<?php echo $result['is_correct'] ? 'success' : 'danger'; ?>">
                                            <?php echo $result['selected_answer'] ?? 'Not answered'; ?>
                                        </span>
                                        <?php if (!$result['is_correct']): ?>
                                            <span class="ms-2">
                                                <strong>Correct Answer:</strong> 
                                                <span class="badge bg-success"><?php echo $result['correct_answer']; ?></span>
                                            </span>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add some animation to the result cards
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.question-card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.classList.add('fade-in');
                }, index * 100);
            });
        });
    </script>
</body>
</html>
