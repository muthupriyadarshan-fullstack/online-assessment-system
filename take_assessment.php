<?php
require_once 'config/database.php';
require_once 'config/session.php';

$db = new Database();
requireUser();

$assessment_id = $_GET['id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$assessment_id) {
    header('Location: user_dashboard.php');
    exit();
}

// Get assessment details
$assessment = $db->fetchOne("
    SELECT * FROM assessments 
    WHERE id = ? AND is_active = 1
", [$assessment_id]);

if (!$assessment) {
    header('Location: user_dashboard.php');
    exit();
}

// Get questions for this assessment
$questions = $db->fetchAll("
    SELECT * FROM questions 
    WHERE assessment_id = ? 
    ORDER BY id
", [$assessment_id]);

if (empty($questions)) {
    header('Location: user_dashboard.php');
    exit();
}

// Check if user has already completed this assessment
$existing_attempt = $db->fetchOne("
    SELECT * FROM user_attempts 
    WHERE user_id = ? AND assessment_id = ? AND is_completed = 1
    ORDER BY completed_at DESC
    LIMIT 1
", [$user_id, $assessment_id]);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $answers = $_POST['answers'] ?? [];
    
    // Create new attempt
    $attempt_id = $db->query("
        INSERT INTO user_attempts (user_id, assessment_id, total_questions, started_at, completed_at, is_completed) 
        VALUES (?, ?, ?, NOW(), NOW(), 1)
    ", [$user_id, $assessment_id, count($questions)]);
    
    $attempt_id = $db->lastInsertId();
    
    $correct_answers = 0;
    $total_score = 0;
    
    // Process each answer
    foreach ($questions as $question) {
        $selected_answer = $answers[$question['id']] ?? null;
        $is_correct = ($selected_answer === $question['correct_answer']);
        
        if ($is_correct) {
            $correct_answers++;
            $total_score += $question['points'];
        }
        
        $db->query("
            INSERT INTO user_answers (attempt_id, question_id, selected_answer, is_correct) 
            VALUES (?, ?, ?, ?)
        ", [$attempt_id, $question['id'], $selected_answer, $is_correct]);
    }
    
    // Calculate percentage score
    $percentage_score = round(($correct_answers / count($questions)) * 100);
    
    // Update attempt with score
    $db->query("
        UPDATE user_attempts 
        SET score = ?, correct_answers = ?
        WHERE id = ?
    ", [$percentage_score, $correct_answers, $attempt_id]);
    
    // Redirect to results page
    header("Location: assessment_result.php?attempt_id=$attempt_id");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($assessment['title']); ?> - Assessment System</title>
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
                    <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </nav>

    <!-- Assessment Header -->
    <div class="container py-4">
        <div class="row">
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h3 class="mb-0">
                                    <i class="fas fa-clipboard-list me-2"></i>
                                    <?php echo htmlspecialchars($assessment['title']); ?>
                                </h3>
                                <p class="mb-0 text-white-50"><?php echo htmlspecialchars($assessment['description']); ?></p>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <div class="d-flex flex-column flex-md-row gap-2">
                                    <span class="badge bg-light text-dark">
                                        <i class="fas fa-question-circle me-1"></i>
                                        <?php echo count($questions); ?> Questions
                                    </span>
                                    <span class="badge bg-light text-dark">
                                        <i class="fas fa-clock me-1"></i>
                                        <?php echo $assessment['time_limit']; ?> Minutes
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assessment Form -->
        <form method="POST" id="assessmentForm">
            <div class="row">
                <div class="col-lg-8">
                    <?php foreach ($questions as $index => $question): ?>
                        <div class="question-card" id="question-<?php echo $question['id']; ?>">
                            <div class="question-text">
                                <span class="badge bg-purple me-2"><?php echo $index + 1; ?></span>
                                <?php echo htmlspecialchars($question['question_text']); ?>
                            </div>
                            
                            <div class="options">
                                <div class="option" onclick="selectOption(<?php echo $question['id']; ?>, 'A')">
                                    <input type="radio" name="answers[<?php echo $question['id']; ?>]" value="A" id="q<?php echo $question['id']; ?>_a">
                                    <label for="q<?php echo $question['id']; ?>_a" class="w-100">
                                        <strong>A.</strong> <?php echo htmlspecialchars($question['option_a']); ?>
                                    </label>
                                </div>
                                
                                <div class="option" onclick="selectOption(<?php echo $question['id']; ?>, 'B')">
                                    <input type="radio" name="answers[<?php echo $question['id']; ?>]" value="B" id="q<?php echo $question['id']; ?>_b">
                                    <label for="q<?php echo $question['id']; ?>_b" class="w-100">
                                        <strong>B.</strong> <?php echo htmlspecialchars($question['option_b']); ?>
                                    </label>
                                </div>
                                
                                <div class="option" onclick="selectOption(<?php echo $question['id']; ?>, 'C')">
                                    <input type="radio" name="answers[<?php echo $question['id']; ?>]" value="C" id="q<?php echo $question['id']; ?>_c">
                                    <label for="q<?php echo $question['id']; ?>_c" class="w-100">
                                        <strong>C.</strong> <?php echo htmlspecialchars($question['option_c']); ?>
                                    </label>
                                </div>
                                
                                <div class="option" onclick="selectOption(<?php echo $question['id']; ?>, 'D')">
                                    <input type="radio" name="answers[<?php echo $question['id']; ?>]" value="D" id="q<?php echo $question['id']; ?>_d">
                                    <label for="q<?php echo $question['id']; ?>_d" class="w-100">
                                        <strong>D.</strong> <?php echo htmlspecialchars($question['option_d']); ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <div class="card sticky-top">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>Assessment Info
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h6>Progress</h6>
                                <div class="progress mb-2">
                                    <div class="progress-bar" id="progressBar" style="width: 0%"></div>
                                </div>
                                <small class="text-muted">
                                    <span id="answeredCount">0</span> of <?php echo count($questions); ?> questions answered
                                </small>
                            </div>
                            
                            <div class="mb-3">
                                <h6>Time Remaining</h6>
                                <div class="text-center">
                                    <div class="display-6 text-purple" id="timeRemaining">
                                        <?php echo $assessment['time_limit']; ?>:00
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <h6>Question Navigation</h6>
                                <div class="row g-2" id="questionNav">
                                    <?php for ($i = 1; $i <= count($questions); $i++): ?>
                                        <div class="col-3">
                                            <button type="button" class="btn btn-outline-primary btn-sm w-100" 
                                                    onclick="scrollToQuestion(<?php echo $i; ?>)">
                                                <?php echo $i; ?>
                                            </button>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success btn-lg" id="submitBtn" disabled>
                                    <i class="fas fa-check me-2"></i>Submit Assessment
                                </button>
                                <button type="button" class="btn btn-outline-warning" onclick="confirmSubmit()">
                                    <i class="fas fa-save me-2"></i>Save & Submit
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>Confirm Submission
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to submit your assessment?</p>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        You have answered <span id="modalAnsweredCount">0</span> out of <?php echo count($questions); ?> questions.
                    </div>
                    <p class="mb-0">Once submitted, you cannot change your answers.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" onclick="submitAssessment()">
                        <i class="fas fa-check me-2"></i>Submit Assessment
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let timeRemaining = <?php echo $assessment['time_limit'] * 60; ?>; // Convert to seconds
        let timer;
        let answeredQuestions = new Set();
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            startTimer();
            updateProgress();
        });
        
        // Timer functionality
        function startTimer() {
            timer = setInterval(function() {
                timeRemaining--;
                updateTimeDisplay();
                
                if (timeRemaining <= 0) {
                    clearInterval(timer);
                    alert('Time is up! Your assessment will be submitted automatically.');
                    submitAssessment();
                }
            }, 1000);
        }
        
        function updateTimeDisplay() {
            const minutes = Math.floor(timeRemaining / 60);
            const seconds = timeRemaining % 60;
            document.getElementById('timeRemaining').textContent = 
                `${minutes}:${seconds.toString().padStart(2, '0')}`;
        }
        
        // Option selection
        function selectOption(questionId, option) {
            // Remove selected class from all options for this question
            const questionCard = document.getElementById(`question-${questionId}`);
            const options = questionCard.querySelectorAll('.option');
            options.forEach(opt => opt.classList.remove('selected'));
            
            // Add selected class to clicked option
            const selectedOption = questionCard.querySelector(`input[value="${option}"]`).closest('.option');
            selectedOption.classList.add('selected');
            
            // Check the radio button
            document.querySelector(`input[name="answers[${questionId}]"][value="${option}"]`).checked = true;
            
            // Update progress
            answeredQuestions.add(questionId);
            updateProgress();
        }
        
        // Update progress
        function updateProgress() {
            const totalQuestions = <?php echo count($questions); ?>;
            const answeredCount = answeredQuestions.size;
            const progress = (answeredCount / totalQuestions) * 100;
            
            document.getElementById('progressBar').style.width = progress + '%';
            document.getElementById('answeredCount').textContent = answeredCount;
            document.getElementById('modalAnsweredCount').textContent = answeredCount;
            
            // Enable submit button if all questions answered
            if (answeredCount === totalQuestions) {
                document.getElementById('submitBtn').disabled = false;
            }
        }
        
        // Question navigation
        function scrollToQuestion(questionNumber) {
            const questionCard = document.getElementById(`question-${questionNumber}`);
            questionCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        
        // Form submission
        function confirmSubmit() {
            const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
            modal.show();
        }
        
        function submitAssessment() {
            document.getElementById('assessmentForm').submit();
        }
        
        // Auto-save functionality (optional)
        function autoSave() {
            // This could be implemented to save progress periodically
            console.log('Auto-saving progress...');
        }
        
        // Prevent accidental page refresh
        window.addEventListener('beforeunload', function(e) {
            if (answeredQuestions.size > 0) {
                e.preventDefault();
                e.returnValue = 'You have unsaved answers. Are you sure you want to leave?';
            }
        });
    </script>
</body>
</html>
