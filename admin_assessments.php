<?php
require_once 'config/database.php';
require_once 'config/session.php';

$db = new Database();
requireAdmin();

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'create_assessment') {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $time_limit = intval($_POST['time_limit']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($title)) {
            $error = 'Please enter assessment title.';
        } else {
            try {
                $db->query("
                    INSERT INTO assessments (title, description, total_questions, time_limit, created_by, is_active) 
                    VALUES (?, ?, 0, ?, ?, ?)
                ", [$title, $description, $time_limit, $_SESSION['user_id'], $is_active]);
                
                $message = 'Assessment created successfully!';
            } catch (Exception $e) {
                $error = 'Error creating assessment: ' . $e->getMessage();
            }
        }
    }
    
    elseif ($action == 'update_assessment') {
        $assessment_id = $_POST['assessment_id'];
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $time_limit = intval($_POST['time_limit']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        try {
            $db->query("
                UPDATE assessments 
                SET title = ?, description = ?, time_limit = ?, is_active = ?
                WHERE id = ?
            ", [$title, $description, $time_limit, $is_active, $assessment_id]);
            
            $message = 'Assessment updated successfully!';
        } catch (Exception $e) {
            $error = 'Error updating assessment: ' . $e->getMessage();
        }
    }
    
    elseif ($action == 'delete_assessment') {
        $assessment_id = $_POST['assessment_id'];
        try {
            $db->query("DELETE FROM assessments WHERE id = ?", [$assessment_id]);
            $message = 'Assessment deleted successfully!';
        } catch (Exception $e) {
            $error = 'Error deleting assessment: ' . $e->getMessage();
        }
    }
}

// Get assessments with question counts
$assessments = $db->fetchAll("
    SELECT a.*, 
           (SELECT COUNT(*) FROM questions q WHERE q.assessment_id = a.id) as question_count,
           (SELECT COUNT(*) FROM user_attempts ua WHERE ua.assessment_id = a.id AND ua.is_completed = 1) as attempt_count
    FROM assessments a
    ORDER BY a.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assessment Management - Admin</title>
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
                        <a class="nav-link active" href="admin_assessments.php">
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
                            <i class="fas fa-clipboard-list me-2 text-purple"></i>
                            Assessment Management
                        </h2>
                        <p class="card-text text-muted">
                            Create, edit, and manage assessments for your users.
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
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAssessmentModal">
                        <i class="fas fa-plus me-2"></i>Create Assessment
                    </button>
                    <a href="admin_questions.php" class="btn btn-outline-primary">
                        <i class="fas fa-question-circle me-2"></i>Manage Questions
                    </a>
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
                            <h5 class="text-muted">No assessments found</h5>
                            <p class="text-muted">Create your first assessment to get started.</p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($assessments as $assessment): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><?php echo htmlspecialchars($assessment['title']); ?></h5>
                                <span class="badge bg-<?php echo $assessment['is_active'] ? 'success' : 'secondary'; ?>">
                                    <?php echo $assessment['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <p class="card-text text-muted mb-3">
                                    <?php echo htmlspecialchars($assessment['description']); ?>
                                </p>
                                
                                <div class="row text-center mb-3">
                                    <div class="col-4">
                                        <div class="fw-bold text-purple"><?php echo $assessment['question_count']; ?></div>
                                        <small class="text-muted">Questions</small>
                                    </div>
                                    <div class="col-4">
                                        <div class="fw-bold text-purple"><?php echo $assessment['time_limit']; ?></div>
                                        <small class="text-muted">Minutes</small>
                                    </div>
                                    <div class="col-4">
                                        <div class="fw-bold text-purple"><?php echo $assessment['attempt_count']; ?></div>
                                        <small class="text-muted">Attempts</small>
                                    </div>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <button class="btn btn-outline-primary btn-sm flex-fill" 
                                            onclick="editAssessment(<?php echo htmlspecialchars(json_encode($assessment)); ?>)">
                                        <i class="fas fa-edit me-1"></i>Edit
                                    </button>
                                    <a href="admin_questions.php?assessment_id=<?php echo $assessment['id']; ?>" 
                                       class="btn btn-outline-success btn-sm flex-fill">
                                        <i class="fas fa-question-circle me-1"></i>Questions
                                    </a>
                                    <button class="btn btn-outline-danger btn-sm" 
                                            onclick="deleteAssessment(<?php echo $assessment['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-footer text-muted">
                                <small>
                                    <i class="fas fa-calendar me-1"></i>
                                    Created <?php echo date('M j, Y', strtotime($assessment['created_at'])); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Create Assessment Modal -->
    <div class="modal fade" id="createAssessmentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Create New Assessment
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_assessment">
                        
                        <div class="form-group mb-3">
                            <label for="title" class="form-label">Assessment Title *</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>

                        <div class="form-group mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>

                        <div class="form-group mb-3">
                            <label for="time_limit" class="form-label">Time Limit (minutes)</label>
                            <input type="number" class="form-control" id="time_limit" name="time_limit" value="60" min="1">
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                            <label class="form-check-label" for="is_active">
                                Active (users can take this assessment)
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Create Assessment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Assessment Modal -->
    <div class="modal fade" id="editAssessmentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Edit Assessment
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_assessment">
                        <input type="hidden" name="assessment_id" id="edit_assessment_id">
                        
                        <div class="form-group mb-3">
                            <label for="edit_title" class="form-label">Assessment Title *</label>
                            <input type="text" class="form-control" id="edit_title" name="title" required>
                        </div>

                        <div class="form-group mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>

                        <div class="form-group mb-3">
                            <label for="edit_time_limit" class="form-label">Time Limit (minutes)</label>
                            <input type="number" class="form-control" id="edit_time_limit" name="time_limit" min="1">
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active">
                            <label class="form-check-label" for="edit_is_active">
                                Active (users can take this assessment)
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Assessment
                        </button>
                    </div>
                </form>
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
                    <p>Are you sure you want to delete this assessment?</p>
                    <p class="text-muted">This will also delete all associated questions and user attempts.</p>
                    <p class="text-danger"><strong>This action cannot be undone!</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete_assessment">
                        <input type="hidden" name="assessment_id" id="deleteAssessmentId">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>Delete Assessment
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editAssessment(assessment) {
            document.getElementById('edit_assessment_id').value = assessment.id;
            document.getElementById('edit_title').value = assessment.title;
            document.getElementById('edit_description').value = assessment.description;
            document.getElementById('edit_time_limit').value = assessment.time_limit;
            document.getElementById('edit_is_active').checked = assessment.is_active == 1;
            
            const modal = new bootstrap.Modal(document.getElementById('editAssessmentModal'));
            modal.show();
        }

        function deleteAssessment(assessmentId) {
            document.getElementById('deleteAssessmentId').value = assessmentId;
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }
    </script>
</body>
</html>
