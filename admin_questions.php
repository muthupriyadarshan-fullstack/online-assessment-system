<?php
require_once 'config/database.php';
require_once 'config/session.php';

$db = new Database();
requireAdmin();

$message = '';
$error = '';

// Handle AJAX requests
if (isset($_GET['action']) && $_GET['action'] == 'get_question') {
    $question_id = $_GET['question_id'] ?? 0;
    
    try {
        $question = $db->fetchOne("
            SELECT q.*, a.title as assessment_title
            FROM questions q
            JOIN assessments a ON q.assessment_id = a.id
            WHERE q.id = ?
        ", [$question_id]);
        
        if ($question) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'question' => $question
            ]);
        } else {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Question not found'
            ]);
        }
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching question: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'add_question') {
        $assessment_id = $_POST['assessment_id'];
        $question_text = trim($_POST['question_text']);
        $option_a = trim($_POST['option_a']);
        $option_b = trim($_POST['option_b']);
        $option_c = trim($_POST['option_c']);
        $option_d = trim($_POST['option_d']);
        $correct_answer = $_POST['correct_answer'];
        $points = intval($_POST['points']);
        
        if (empty($question_text) || empty($option_a) || empty($option_b) || empty($option_c) || empty($option_d)) {
            $error = 'Please fill in all fields.';
        } else {
            try {
                $db->query("
                    INSERT INTO questions (assessment_id, question_text, option_a, option_b, option_c, option_d, correct_answer, points) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ", [$assessment_id, $question_text, $option_a, $option_b, $option_c, $option_d, $correct_answer, $points]);
                
                $message = 'Question added successfully!';
            } catch (Exception $e) {
                $error = 'Error adding question: ' . $e->getMessage();
            }
        }
    }
    
    elseif ($action == 'upload_csv') {
        if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
            $assessment_id = $_POST['assessment_id'];
            $file = $_FILES['csv_file']['tmp_name'];
            
            if (($handle = fopen($file, 'r')) !== FALSE) {
                $row = 0;
                $success_count = 0;
                $error_count = 0;
                
                while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                    $row++;
                    
                    // Skip header row
                    if ($row == 1) continue;
                    
                    // Validate CSV format: question, option_a, option_b, option_c, option_d, correct_answer, points
                    if (count($data) >= 6) {
                        $question_text = trim($data[0]);
                        $option_a = trim($data[1]);
                        $option_b = trim($data[2]);
                        $option_c = trim($data[3]);
                        $option_d = trim($data[4]);
                        $correct_answer = strtoupper(trim($data[5]));
                        $points = isset($data[6]) ? intval($data[6]) : 1;
                        
                        if (!empty($question_text) && !empty($option_a) && !empty($option_b) && 
                            !empty($option_c) && !empty($option_d) && in_array($correct_answer, ['A', 'B', 'C', 'D'])) {
                            
                            try {
                                $db->query("
                                    INSERT INTO questions (assessment_id, question_text, option_a, option_b, option_c, option_d, correct_answer, points) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                                ", [$assessment_id, $question_text, $option_a, $option_b, $option_c, $option_d, $correct_answer, $points]);
                                
                                $success_count++;
                            } catch (Exception $e) {
                                $error_count++;
                            }
                        } else {
                            $error_count++;
                        }
                    } else {
                        $error_count++;
                    }
                }
                fclose($handle);
                
                if ($success_count > 0) {
                    $message = "Successfully imported $success_count questions.";
                    if ($error_count > 0) {
                        $message .= " $error_count rows had errors and were skipped.";
                    }
                } else {
                    $error = 'No valid questions were imported. Please check your CSV format.';
                }
            } else {
                $error = 'Error reading CSV file.';
            }
        } else {
            $error = 'Please select a valid CSV file.';
        }
    }
    
    elseif ($action == 'delete_question') {
        $question_id = $_POST['question_id'];
        try {
            $db->query("DELETE FROM questions WHERE id = ?", [$question_id]);
            $message = 'Question deleted successfully!';
        } catch (Exception $e) {
            $error = 'Error deleting question: ' . $e->getMessage();
        }
    }
}

// Get assessments for dropdown
$assessments = $db->fetchAll("SELECT * FROM assessments ORDER BY title");

// Get questions with pagination
$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$questions = $db->fetchAll("
    SELECT q.*, a.title as assessment_title
    FROM questions q
    JOIN assessments a ON q.assessment_id = a.id
    ORDER BY q.id DESC
    LIMIT ? OFFSET ?
", [$limit, $offset]);

$total_questions = $db->fetchOne("SELECT COUNT(*) as count FROM questions")['count'];
$total_pages = ceil($total_questions / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Question Management - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .question-view {
            font-family: 'Inter', sans-serif;
        }
        
        .question-header h5 {
            color: #6B46C1;
            font-weight: 600;
        }
        
        .question-content {
            border-left: 4px solid #6B46C1;
            background-color: #f8f9fa !important;
        }
        
        .option-item {
            padding: 12px 16px;
            margin-bottom: 8px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        
        .option-item:hover {
            background-color: #f8f9fa;
            border-color: #6B46C1;
        }
        
        .correct-option {
            background-color: #d1edff;
            border-color: #28a745;
            border-left: 4px solid #28a745;
        }
        
        .option-key {
            min-width: 40px;
            text-align: center;
            font-size: 16px;
        }
        
        .option-text {
            font-size: 15px;
            line-height: 1.5;
        }
        
        .meta-item h6 {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge.bg-purple {
            background-color: #6B46C1 !important;
        }
        
        .spinner-border {
            width: 2rem;
            height: 2rem;
        }
    </style>
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
                        <a class="nav-link active" href="admin_questions.php">
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
                            <i class="fas fa-question-circle me-2 text-purple"></i>
                            Question Management
                        </h2>
                        <p class="card-text text-muted">
                            Add, edit, and manage questions for your assessments.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addQuestionModal">
                        <i class="fas fa-plus me-2"></i>Add Question
                    </button>
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#uploadCsvModal">
                        <i class="fas fa-upload me-2"></i>Upload CSV
                    </button>
                    <a href="admin_questions.php" class="btn btn-outline-secondary">
                        <i class="fas fa-refresh me-2"></i>Refresh
                    </a>
                </div>
            </div>
        </div>

        <!-- Questions Table -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>All Questions (<?php echo $total_questions; ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($questions)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-question-circle fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No questions found</h5>
                                <p class="text-muted">Start by adding your first question or uploading a CSV file.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Assessment</th>
                                            <th>Question</th>
                                            <th>Correct Answer</th>
                                            <th>Points</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($questions as $question): ?>
                                            <tr>
                                                <td><?php echo $question['id']; ?></td>
                                                <td>
                                                    <span class="badge bg-purple"><?php echo htmlspecialchars($question['assessment_title']); ?></span>
                                                </td>
                                                <td>
                                                    <div class="question-preview">
                                                        <?php echo htmlspecialchars(substr($question['question_text'], 0, 100)); ?>
                                                        <?php if (strlen($question['question_text']) > 100): ?>...<?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-success"><?php echo $question['correct_answer']; ?></span>
                                                </td>
                                                <td><?php echo $question['points']; ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-primary" onclick="viewQuestion(<?php echo $question['id']; ?>)">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button class="btn btn-outline-danger" onclick="deleteQuestion(<?php echo $question['id']; ?>)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                                <nav aria-label="Questions pagination">
                                    <ul class="pagination justify-content-center">
                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Question Modal -->
    <div class="modal fade" id="addQuestionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Add New Question
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_question">
                        
                        <div class="form-group mb-3">
                            <label for="assessment_id" class="form-label">Assessment *</label>
                            <select class="form-control" id="assessment_id" name="assessment_id" required>
                                <option value="">Select Assessment</option>
                                <?php foreach ($assessments as $assessment): ?>
                                    <option value="<?php echo $assessment['id']; ?>">
                                        <?php echo htmlspecialchars($assessment['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label for="question_text" class="form-label">Question Text *</label>
                            <textarea class="form-control" id="question_text" name="question_text" rows="3" required></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="option_a" class="form-label">Option A *</label>
                                    <input type="text" class="form-control" id="option_a" name="option_a" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="option_b" class="form-label">Option B *</label>
                                    <input type="text" class="form-control" id="option_b" name="option_b" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="option_c" class="form-label">Option C *</label>
                                    <input type="text" class="form-control" id="option_c" name="option_c" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="option_d" class="form-label">Option D *</label>
                                    <input type="text" class="form-control" id="option_d" name="option_d" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="correct_answer" class="form-label">Correct Answer *</label>
                                    <select class="form-control" id="correct_answer" name="correct_answer" required>
                                        <option value="">Select Answer</option>
                                        <option value="A">A</option>
                                        <option value="B">B</option>
                                        <option value="C">C</option>
                                        <option value="D">D</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="points" class="form-label">Points</label>
                                    <input type="number" class="form-control" id="points" name="points" value="1" min="1">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Add Question
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Upload CSV Modal -->
    <div class="modal fade" id="uploadCsvModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-upload me-2"></i>Upload Questions from CSV
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="upload_csv">
                        
                        <div class="form-group mb-3">
                            <label for="csv_assessment_id" class="form-label">Assessment *</label>
                            <select class="form-control" id="csv_assessment_id" name="assessment_id" required>
                                <option value="">Select Assessment</option>
                                <?php foreach ($assessments as $assessment): ?>
                                    <option value="<?php echo $assessment['id']; ?>">
                                        <?php echo htmlspecialchars($assessment['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label for="csv_file" class="form-label">CSV File *</label>
                            <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
                            <small class="form-text text-muted">
                                CSV format: question, option_a, option_b, option_c, option_d, correct_answer, points
                            </small>
                        </div>

                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle me-2"></i>CSV Format Requirements:</h6>
                            <ul class="mb-0">
                                <li>First row should be headers (will be skipped)</li>
                                <li>Correct answer should be A, B, C, or D</li>
                                <li>Points column is optional (defaults to 1)</li>
                                <li>All fields except points are required</li>
                            </ul>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload me-2"></i>Upload Questions
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Question Modal -->
    <div class="modal fade" id="viewQuestionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-eye me-2"></i>View Question Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="questionDetails">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading question details...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this question?</p>
                    <p class="text-muted">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete_question">
                        <input type="hidden" name="question_id" id="deleteQuestionId">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>Delete Question
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteQuestion(questionId) {
            document.getElementById('deleteQuestionId').value = questionId;
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }

        function viewQuestion(questionId) {
            // Show the modal
            const modal = new bootstrap.Modal(document.getElementById('viewQuestionModal'));
            modal.show();
            
            // Reset the content to loading state
            document.getElementById('questionDetails').innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading question details...</p>
                </div>
            `;
            
            // Fetch question details via AJAX
            fetch(`?action=get_question&question_id=${questionId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayQuestion(data.question);
                    } else {
                        displayError(data.message);
                    }
                })
                .catch(error => {
                    displayError('Error loading question details: ' + error.message);
                });
        }
        
        function displayQuestion(question) {
            const correctAnswer = question.correct_answer;
            const options = [
                { key: 'A', value: question.option_a },
                { key: 'B', value: question.option_b },
                { key: 'C', value: question.option_c },
                { key: 'D', value: question.option_d }
            ];
            
            let optionsHtml = '';
            options.forEach(option => {
                const isCorrect = option.key === correctAnswer;
                const badgeClass = isCorrect ? 'bg-success' : 'bg-secondary';
                const iconClass = isCorrect ? 'fas fa-check-circle' : 'far fa-circle';
                
                optionsHtml += `
                    <div class="option-item ${isCorrect ? 'correct-option' : ''}">
                        <div class="d-flex align-items-center">
                            <span class="option-key me-3">
                                <i class="${iconClass} me-1"></i>
                                <strong>${option.key}</strong>
                            </span>
                            <span class="option-text flex-grow-1">${option.value}</span>
                            <span class="badge ${badgeClass} ms-2">
                                ${isCorrect ? 'Correct' : 'Option'}
                            </span>
                        </div>
                    </div>
                `;
            });
            
            document.getElementById('questionDetails').innerHTML = `
                <div class="question-view">
                    <!-- Question Header -->
                    <div class="question-header mb-4">
                        <div class="row">
                            <div class="col-md-8">
                                <h6 class="text-muted mb-1">Assessment</h6>
                                <h5 class="mb-2">
                                    <span class="badge bg-purple">${question.assessment_title}</span>
                                </h5>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <h6 class="text-muted mb-1">Question ID</h6>
                                <h5 class="mb-2">#${question.id}</h5>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Question Text -->
                    <div class="question-text mb-4">
                        <h6 class="text-muted mb-2">Question</h6>
                        <div class="question-content p-3 bg-light rounded">
                            <p class="mb-0">${question.question_text}</p>
                        </div>
                    </div>
                    
                    <!-- Options -->
                    <div class="question-options mb-4">
                        <h6 class="text-muted mb-3">Answer Options</h6>
                        <div class="options-container">
                            ${optionsHtml}
                        </div>
                    </div>
                    
                    <!-- Question Meta -->
                    <div class="question-meta">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="meta-item">
                                    <h6 class="text-muted mb-1">Points</h6>
                                    <span class="badge bg-info fs-6">${question.points} point${question.points !== 1 ? 's' : ''}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="meta-item">
                                    <h6 class="text-muted mb-1">Correct Answer</h6>
                                    <span class="badge bg-success fs-6">Option ${correctAnswer}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
        
        function displayError(message) {
            document.getElementById('questionDetails').innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                    <h5 class="text-danger">Error Loading Question</h5>
                    <p class="text-muted">${message}</p>
                </div>
            `;
        }
    </script>
</body>
</html>
