<?php
// user/view_task.php - Modern Task View with Chat
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'employee') {
    header('Location: ../auth/login.php');
    exit;
}

$task_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($task_id == 0) {
    header('Location: my_tasks.php');
    exit;
}

// Fetch task data
$stmt = $conn->prepare("SELECT t.*, u.name as assigned_by_name, (SELECT GROUP_CONCAT(name SEPARATOR ', ') FROM users WHERE FIND_IN_SET(id, t.observer_id) > 0) as observer_name FROM tasks t JOIN users u ON u.role='admin' WHERE t.id = ? AND (t.employee_id = ? OR FIND_IN_SET(?, t.observer_id) > 0) LIMIT 1");
$stmt->bind_param('iii', $task_id, $_SESSION['user_id'], $_SESSION['user_id']);
$stmt->execute();
$task = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$task) {
    echo "Task not found or access denied.";
    exit;
}

// Mark messages as read
try {
    $conn->query("UPDATE task_comments SET is_read = 1 WHERE task_id = $task_id AND user_id != {$_SESSION['user_id']}");
} catch (Exception $e) {}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_comment'])) {
    $message = trim($_POST['message']);
    
    $attachment = null;
    if (isset($_FILES['chat_file']) && $_FILES['chat_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../assets/uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $file_name = time() . '_taskchat_' . basename($_FILES['chat_file']['name']);
        $target_path = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES['chat_file']['tmp_name'], $target_path)) {
            $attachment = $file_name;
        }
    }

    if (!empty($message) || $attachment) {
        $stmt = $conn->prepare("INSERT INTO task_comments (task_id, user_id, message, attachment) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $task_id, $_SESSION['user_id'], $message, $attachment);
        $stmt->execute();
        $stmt->close();
        header("Location: view_task.php?id=$task_id");
        exit;
    }
}

// Handle task complete locally
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_complete'])) {
    $stmt = $conn->prepare("UPDATE tasks SET status='completed', completed_at=NOW() WHERE id=?");
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    $stmt->close();
    header("Location: view_task.php?id=$task_id");
    exit;
}

include '../includes/header.php';
?>
<div class="page-container">
    <?php include '../includes/sidebar_user.php'; ?>
    <div class="main-content">
        <div class="container-fluid p-0">
            <div class="mb-3 d-flex align-items-center">
                <a href="my_tasks.php" class="btn btn-sm btn-outline-secondary me-3"><i class="fas fa-arrow-left"></i> Back</a>
                <h4 class="mb-0 text-dark fw-bold">Task: <?php echo htmlspecialchars($task['title']); ?></h4>
            </div>

            <div class="task-detail-container">
                <!-- Left Pane: Task Details -->
                <div class="task-info-pane">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <span class="badge <?php echo $task['status'] === 'completed' ? 'bg-success' : 'bg-danger'; ?> fs-6 p-2">
                            <i class="fas <?php echo $task['status'] === 'completed' ? 'fa-check-circle' : 'fa-hourglass-half'; ?> me-1"></i>
                            <?php echo ucfirst($task['status']); ?>
                        </span>
                        <?php if ($task['status'] !== 'completed' && $task['employee_id'] == $_SESSION['user_id']): ?>
                            <form method="POST" class="m-0">
                                <button type="submit" name="mark_complete" class="btn btn-primary btn-sm">
                                    <i class="fas fa-check me-1"></i> Complete Task
                                </button>
                            </form>
                        <?php elseif (in_array($_SESSION['user_id'], explode(',', $task['observer_id'] ?? '')) && $task['employee_id'] != $_SESSION['user_id']): ?>
                            <span class="badge bg-info"><i class="fas fa-eye me-1"></i> Observer Access</span>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <p class="text-muted fw-bold mb-1"><i class="fas fa-align-left me-1"></i> Description</p>
                        <div class="bg-light p-3 rounded-3" style="font-size: 0.95rem; border: 1px solid var(--border-color);">
                            <?php echo nl2br(htmlspecialchars($task['description']) ?: 'No description provided.'); ?>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4">
                            <p class="text-muted fw-bold mb-1"><i class="fas fa-calendar-alt me-1"></i> Deadline</p>
                            <p class="<?php echo (strtotime($task['due_date']) < time() && $task['status'] == 'pending') ? 'text-danger fw-bold' : ''; ?>">
                                <?php echo date('F d, Y', strtotime($task['due_date'])); ?>
                            </p>
                        </div>
                        <div class="col-md-4">
                            <p class="text-muted fw-bold mb-1"><i class="fas fa-user-tie me-1"></i> Assigned By</p>
                            <p><?php echo htmlspecialchars($task['assigned_by_name']); ?></p>
                        </div>
                        <div class="col-md-4">
                            <p class="text-muted fw-bold mb-1"><i class="fas fa-eye me-1"></i> Observer</p>
                            <p>
                                <?php if($task['observer_name']): ?>
                                    <span class="badge bg-info p-2"><i class="fas fa-eye me-1"></i> <?php echo htmlspecialchars($task['observer_name']); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">None</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>

                    <?php if ($task['attachment'] || $task['user_attachment']): ?>
                    <div class="mb-4">
                        <p class="text-muted fw-bold mb-2"><i class="fas fa-paperclip me-1"></i> Attachments</p>
                        <div class="d-flex flex-wrap gap-2">
                            <?php if ($task['attachment']): ?>
                                <a href="../assets/uploads/<?php echo htmlspecialchars($task['attachment']); ?>" download target="_blank" class="btn btn-sm btn-outline-primary shadow-sm">
                                    <i class="fas fa-download me-1"></i> Download Admin File
                                </a>
                            <?php endif; ?>
                            <?php if ($task['user_attachment']): ?>
                                <a href="../assets/uploads/<?php echo htmlspecialchars($task['user_attachment']); ?>" download target="_blank" class="btn btn-sm btn-outline-info shadow-sm">
                                    <i class="fas fa-download me-1"></i> Download Your File
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Right Pane: Chat -->
                <div class="task-chat-pane">
                    <div class="task-chat-header">
                        <h6 class="mb-0 fw-bold"><i class="fas fa-comments text-primary me-2"></i> Task Discussion</h6>
                        <span class="badge bg-secondary">
                        <?php
                            $msg_count = $conn->query("SELECT count(*) as c FROM task_comments WHERE task_id = $task_id")->fetch_assoc()['c'];
                            echo $msg_count . " Messages";
                        ?>
                        </span>
                    </div>
                    <div class="task-chat-messages" id="chatbox">
                        <?php
                        // Fetch comments
                        $comments = $conn->query("SELECT c.*, u.name, u.role FROM task_comments c JOIN users u ON c.user_id = u.id WHERE c.task_id = $task_id ORDER BY c.created_at ASC");
                        if ($comments->num_rows > 0) {
                            while ($c = $comments->fetch_assoc()) {
                                $is_me = ($c['user_id'] == $_SESSION['user_id']);
                                ?>
                                <div class="d-flex flex-column <?php echo $is_me ? 'align-items-end' : 'align-items-start'; ?> mb-3">
                                    <div class="message-bubble <?php echo $is_me ? 'me' : 'them'; ?>">
                                        <?php if(!$is_me): ?>
                                            <div class="d-flex align-items-center mb-2">
                                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px; font-size: 0.7rem;">
                                                    <?php echo strtoupper(substr($c['name'], 0, 1)); ?>
                                                </div>
                                                <span class="fw-bold" style="font-size:0.8rem;">
                                                    <?php echo htmlspecialchars($c['name']); ?> 
                                                    <?php if($c['role'] == 'admin') echo ' <i class="fas fa-shield-alt text-warning fa-xs ms-1"></i>'; ?>
                                                    <?php if(in_array($c['user_id'], explode(',', $task['observer_id'] ?? ''))) echo ' <span class="badge bg-info ms-1" style="font-size: 0.65rem;">Observer</span>'; ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if(!empty($c['message'])): ?>
                                            <div class="text-break"><?php echo nl2br(htmlspecialchars($c['message'])); ?></div>
                                        <?php endif; ?>
                                        
                                        <?php if($c['attachment']): ?>
                                            <div class="mt-2 text-start">
                                                <div class="d-inline-flex align-items-center p-2 rounded-3 border bg-white" style="box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                                                    <div class="bg-light rounded p-2 me-2 d-flex align-items-center justify-content-center">
                                                        <i class="fas fa-file-alt fa-lg text-primary"></i>
                                                    </div>
                                                    <div class="me-3 text-start">
                                                        <div class="fw-bold text-truncate text-dark" style="max-width: 130px; font-size: 0.8rem;" title="<?php echo htmlspecialchars($c['attachment']); ?>">
                                                            <?php 
                                                                $filename = htmlspecialchars(basename($c['attachment']));
                                                                echo strlen($filename) > 18 ? substr($filename, 0, 15).'...' : $filename;
                                                            ?>
                                                        </div>
                                                        <div class="text-muted" style="font-size: 0.65rem;">Attachment</div>
                                                    </div>
                                                    <a href="../assets/uploads/<?php echo htmlspecialchars($c['attachment']); ?>" download target="_blank" class="btn btn-sm btn-light border d-flex align-items-center justify-content-center" style="width: 30px; height: 30px; border-radius: 50%;" title="Download">
                                                        <i class="fas fa-download text-primary"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="text-end mt-1 d-flex align-items-center justify-content-end" style="font-size:0.65rem; color: <?php echo $is_me ? '#555' : '#888'; ?>">
                                            <?php echo date('g:i A', strtotime($c['created_at'])); ?>
                                            <?php if($is_me): ?>
                                                <i class="fas fa-check-double ms-1 <?php echo $c['is_read'] ? 'text-success' : 'text-secondary'; ?>" style="font-size: 0.75rem;"></i>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }
                        } else {
                            echo '<div class="text-center text-muted my-5"><i class="fas fa-comment-dots fa-3x mb-3 text-light"></i><br>No comments yet. Start the discussion!</div>';
                        }
                        ?>
                    </div>
                    <div class="task-chat-input">
                        <form method="POST" enctype="multipart/form-data" class="mb-0">
                            <div class="chat-input-wrapper">
                                <label for="chat_file" class="chat-action-btn btn-attach mb-0" style="cursor: pointer;" title="Attach File">
                                    <i class="fas fa-paperclip"></i>
                                </label>
                                <input type="file" name="chat_file" id="chat_file" class="d-none">
                                
                                <textarea name="message" class="chat-textarea" rows="1" placeholder="Type your message here..." oninput="this.style.height = '';this.style.height = this.scrollHeight + 'px'"></textarea>
                                
                                <button type="submit" name="post_comment" class="chat-action-btn btn-send ms-2">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                            <div id="file_name_display" class="text-muted mt-1 ms-4" style="font-size: 0.75rem; display: none;"></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Scroll chat to bottom
    const chatbox = document.getElementById('chatbox');
    if (chatbox) {
        chatbox.scrollTop = chatbox.scrollHeight;
    }

    const fileInput = document.getElementById('chat_file');
    const fileNameDisplay = document.getElementById('file_name_display');
    const submitBtn = document.querySelector('button[name="post_comment"]');
    const messageInput = document.querySelector('textarea[name="message"]');

    if(fileInput && fileNameDisplay) {
        fileInput.addEventListener('change', function() {
            if(this.files && this.files.length > 0) {
                // Show green tick with file name
                fileNameDisplay.innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i></span> ' + this.files[0].name;
                fileNameDisplay.style.display = 'block';
                
                // Allow simple file upload by removing 'required' from textarea conceptually 
                // (Since we check it on server side)
            } else {
                fileNameDisplay.style.display = 'none';
            }
        });
    }

    // Require either a message OR an attachment before form submits
    document.querySelector('form').addEventListener('submit', function(e) {
        if(!messageInput.value.trim() && (!fileInput.files || fileInput.files.length === 0)) {
            e.preventDefault();
            messageInput.focus();
        }
    });
</script>

<?php include '../includes/footer.php'; ?>
