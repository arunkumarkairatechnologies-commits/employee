<?php
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'employee') {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$u_q = $conn->query("SELECT designation FROM users WHERE id=$user_id");
$u_data = $u_q->fetch_assoc();
$user_designation = $conn->real_escape_string($u_data['designation'] ?? '');

// Handle Acknowledge
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acknowledge_ann'])) {
    $ann_id = (int)$_POST['ann_id'];
    $conn->query("INSERT IGNORE INTO announcement_reads (announcement_id, user_id) VALUES ($ann_id, $user_id)");
    header("Location: announcements.php");
    exit;
}

// Handle React
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['react_ann'])) {
    $ann_id = (int)$_POST['ann_id'];
    $reaction = $conn->real_escape_string($_POST['react_ann']);
    
    // Check if the same reaction exists
    $check = $conn->query("SELECT reaction FROM announcement_reactions WHERE announcement_id = $ann_id AND user_id = $user_id");
    if($check->num_rows > 0) {
        $existing = $check->fetch_assoc()['reaction'];
        if($existing === $reaction) {
            // Delete if clicking the same
            $conn->query("DELETE FROM announcement_reactions WHERE announcement_id = $ann_id AND user_id = $user_id");
        } else {
            // Update to new reaction
            $conn->query("UPDATE announcement_reactions SET reaction = '$reaction' WHERE announcement_id = $ann_id AND user_id = $user_id");
        }
    } else {
        // Insert new
        $conn->query("INSERT INTO announcement_reactions (announcement_id, user_id, reaction) VALUES ($ann_id, $user_id, '$reaction')");
    }
    header("Location: announcements.php");
    exit;
}

// Handle Add Reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_reply'])) {
    $ann_id = (int)$_POST['announcement_id'];
    $reply_msg = $conn->real_escape_string(trim($_POST['reply_message']));
    if (!empty($reply_msg)) {
        $conn->query("INSERT INTO announcement_replies (announcement_id, user_id, message) VALUES ($ann_id, $user_id, '$reply_msg')");
        header("Location: announcements.php");
        exit;
    }
}

// Handle Edit Reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_reply'])) {
    $reply_id = (int)$_POST['reply_id'];
    $new_msg = $conn->real_escape_string(trim($_POST['reply_message']));
    if (!empty($new_msg)) {
        $conn->query("UPDATE announcement_replies SET message='$new_msg', is_edited=1 WHERE id=$reply_id AND user_id=$user_id");
        header("Location: announcements.php");
        exit;
    }
}

// Handle Delete Reply
if (isset($_GET['delete_reply'])) {
    $reply_id = (int)$_GET['delete_reply'];
    $conn->query("UPDATE announcement_replies SET is_deleted=1 WHERE id=$reply_id AND user_id=$user_id");
    header("Location: announcements.php");
    exit;
}

include '../includes/header.php';
?>
<div class="page-container">
    <?php include '../includes/sidebar_user.php'; ?>
    <div class="main-content">
        <div class="container-xxl py-4">
            <div class="mb-4 text-center">
                <h2 class="mb-1 text-primary fw-bold"><i class="fas fa-bullhorn me-2 fa-lg"></i>Company Announcements</h2>
                <p class="text-muted mb-0 fs-5">Stay updated with the latest news and updates from the administration.</p>
                <hr class="w-25 mx-auto mt-4 mb-5 border-primary" style="opacity: 0.2">
            </div>
            
            <div class="row justify-content-center">
                <div class="col-lg-10">
                <?php
                $res = $conn->query("SELECT a.*, u.name as admin_name, u.profile_image as admin_image 
                                     FROM announcements a 
                                     LEFT JOIN users u ON a.admin_id = u.id 
                                     WHERE a.target_type='all' OR (a.target_type='designation' AND a.target_value='$user_designation')
                                     ORDER BY a.created_at DESC");
                if ($res && $res->num_rows > 0) {
                    while ($row = $res->fetch_assoc()):
                        $admin_img = !empty($row['admin_image']) ? '../assets/img/' . htmlspecialchars($row['admin_image']) : '../assets/img/admin.png';
                ?>
                    <div class="card border-0 shadow-sm rounded-4 mb-4" style="background-color: #ffffff; border-left: 6px solid var(--primary-color) !important; overflow: hidden;">
                        <div class="card-body p-5 position-relative">
                            <span class="position-absolute top-0 end-0 mt-4 me-4 badge bg-light text-muted border px-3 py-2 rounded-pill fs-6 shadow-sm">
                                <i class="far fa-clock me-1"></i><?php echo date('M d, Y', strtotime($row['created_at'])); ?> <span class="ms-1" style="opacity:0.6"><?php echo date('h:i A', strtotime($row['created_at'])); ?></span>
                            </span>
                            
                            <div class="d-flex align-items-center mb-4 pb-2 border-bottom">
                                <img src="<?php echo $admin_img; ?>" class="rounded-circle shadow-sm me-3 border" width="60" height="60" alt="Admin" onerror="this.src='https://via.placeholder.com/60'">
                                <div>
                                    <h6 class="mb-0 fw-bold text-dark fs-5"><?php echo htmlspecialchars($row['admin_name'] ?? 'Administration'); ?></h6>
                                    <div class="d-flex gap-2 mt-1">
                                        <span class="badge bg-primary px-3 py-1 rounded-pill"><i class="fas fa-info-circle me-1"></i>Official Announcement</span>
                                        <?php if(isset($row['is_critical']) && $row['is_critical']): ?>
                                            <span class="badge bg-danger px-3 py-1 rounded-pill"><i class="fas fa-exclamation-triangle me-1"></i>Critical</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if(isset($row['is_deleted']) && $row['is_deleted']): ?>
                                <h3 class="fw-bold text-dark mb-3 text-decoration-line-through text-muted" style="letter-spacing: -0.5px;"><?php echo htmlspecialchars($row['title']); ?></h3>
                                <div class="text-danger fs-5 mb-3" style="font-style:italic;">
                                    <i class="fas fa-ban me-1"></i> This announcement was deleted.
                                </div>
                            <?php else: ?>
                                <h3 class="fw-bold text-dark mb-3" style="letter-spacing: -0.5px;">
                                    <?php echo htmlspecialchars($row['title']); ?>
                                </h3>
                                
                                <?php 
                                // Check read receipt if critical
                                $is_read = false;
                                if(isset($row['is_critical']) && $row['is_critical']) {
                                    $check_read = $conn->query("SELECT id FROM announcement_reads WHERE announcement_id = {$row['id']} AND user_id = $user_id");
                                    $is_read = ($check_read->num_rows > 0);
                                    if(!$is_read):
                                ?>
                                    <div class="alert alert-danger border-0 rounded-4 shadow-sm mb-4">
                                        <form method="POST" class="d-flex justify-content-between align-items-center mb-0">
                                            <div class="fw-bold"><i class="fas fa-bell me-2"></i>Action Required: Please acknowledge reading this update.</div>
                                            <input type="hidden" name="ann_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" name="acknowledge_ann" class="btn btn-danger btn-sm rounded-pill px-4 fw-bold shadow-sm">I have read this</button>
                                        </form>
                                    </div>
                                <?php 
                                    endif;
                                } 
                                ?>

                                <div class="text-secondary fs-5 rich-text-content" style="line-height: 1.8;">
                                    <?php echo ($row['message']); // Rich HTML text ?>
                                    <?php if(isset($row['is_edited']) && $row['is_edited']): ?>
                                        <small class="text-muted ms-1" style="font-size:0.85rem; opacity:0.7;">(edited)</small>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (!empty($row['attachment'])): ?>
                                <div class="mt-4 pt-3">
                                    <?php 
                                        $ext = strtolower(pathinfo($row['attachment'], PATHINFO_EXTENSION));
                                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])): 
                                    ?>
                                        <img src="../assets/img/<?php echo $row['attachment']; ?>" class="img-fluid rounded-4 shadow-sm" style="max-height: 400px; object-fit: cover;" alt="Attachment">
                                    <?php elseif (in_array($ext, ['mp4', 'webm'])): ?>
                                        <video controls class="w-100 rounded-4 shadow-sm" style="max-height: 400px;">
                                            <source src="../assets/img/<?php echo $row['attachment']; ?>" type="video/<?php echo $ext; ?>">
                                        </video>
                                    <?php else: ?>
                                        <a href="../assets/img/<?php echo $row['attachment']; ?>" target="_blank" class="btn btn-outline-danger rounded-pill px-4 fw-bold shadow-sm">
                                            <i class="fas fa-file-alt me-2"></i>Download Attachment
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Reactions -->
                                <?php if(isset($row['allow_reactions']) && $row['allow_reactions']): ?>
                                <div class="mt-4 pt-2 d-flex align-items-center gap-2">
                                    <?php
                                        // Get reaction counts
                                        $react_counts = $conn->query("SELECT reaction, COUNT(*) as count FROM announcement_reactions WHERE announcement_id = {$row['id']} GROUP BY reaction");
                                        $counts = [];
                                        while($rc = $react_counts->fetch_assoc()) {
                                            $counts[$rc['reaction']] = $rc['count'];
                                        }
                                        $my_react_q = $conn->query("SELECT reaction FROM announcement_reactions WHERE announcement_id = {$row['id']} AND user_id = $user_id");
                                        $my_react = ($my_react_q->num_rows > 0) ? $my_react_q->fetch_assoc()['reaction'] : '';
                                    ?>
                                    <form method="POST" class="d-inline-block m-0 p-0">
                                        <input type="hidden" name="ann_id" value="<?php echo $row['id']; ?>">
                                        
                                        <button type="submit" name="react_ann" value="like" class="btn btn-sm rounded-pill px-3 <?php echo ($my_react=='like')?'btn-primary':'btn-light border'; ?>">
                                            👍 Like <?php if(isset($counts['like']) && $counts['like']>0) echo "<span class='badge bg-white text-dark ms-1'>{$counts['like']}</span>"; ?>
                                        </button>
                                        
                                        <button type="submit" name="react_ann" value="love" class="btn btn-sm rounded-pill px-3 <?php echo ($my_react=='love')?'btn-danger':'btn-light border'; ?>">
                                            ❤️ Love <?php if(isset($counts['love']) && $counts['love']>0) echo "<span class='badge bg-white text-dark ms-1'>{$counts['love']}</span>"; ?>
                                        </button>
                                        
                                        <button type="submit" name="react_ann" value="insightful" class="btn btn-sm rounded-pill px-3 <?php echo ($my_react=='insightful')?'btn-success':'btn-light border'; ?>">
                                            💡 Insightful <?php if(isset($counts['insightful']) && $counts['insightful']>0) echo "<span class='badge bg-white text-dark ms-1'>{$counts['insightful']}</span>"; ?>
                                        </button>
                                    </form>
                                    
                                    <?php if(isset($row['is_critical']) && $row['is_critical'] && $is_read): ?>
                                       <span class="ms-auto text-success fw-bold"><i class="fas fa-check-double me-1"></i>Acknowledged</span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>

                            <?php endif; ?>
                            
                            <!-- Replies Section -->
                            <?php if(!isset($row['allow_comments']) || $row['allow_comments']): ?>
                            <div class="mt-5 pt-4 border-top">
                                <h5 class="fw-bold mb-4 text-dark"><i class="fas fa-comments text-primary me-2"></i>Comments & Replies</h5>
                                
                                <div class="replies-container mb-4 pe-2" style="max-height: 400px; overflow-y: auto;">
                                    <?php
                                    $replies = $conn->query("SELECT r.*, u.name as user_name, u.profile_image FROM announcement_replies r JOIN users u ON r.user_id = u.id WHERE r.announcement_id = " . $row['id'] . " ORDER BY r.created_at ASC");
                                    if ($replies && $replies->num_rows > 0):
                                        while ($reply = $replies->fetch_assoc()):
                                            $reply_img = !empty($reply['profile_image']) ? '../assets/img/' . htmlspecialchars($reply['profile_image']) : 'https://ui-avatars.com/api/?name='.urlencode($reply['user_name']).'&background=random';
                                            $is_my_reply = ($reply['user_id'] == $_SESSION['user_id']);
                                    ?>
                                        <div class="d-flex mb-4">
                                            <img src="<?php echo $reply_img; ?>" class="rounded-circle me-3 mt-1 shadow-sm border" width="45" height="45" alt="User">
                                            <div class="flex-grow-1 bg-light p-4 rounded-4 position-relative border">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <strong class="text-dark fs-6"><?php echo htmlspecialchars($reply['user_name']); ?></strong>
                                                    <small class="text-muted fw-semibold"><?php echo date('M d, g:i a', strtotime($reply['created_at'])); ?></small>
                                                </div>
                                                
                                                <?php if($reply['is_deleted']): ?>
                                                    <div class="text-muted" style="font-style:italic;"><i class="fas fa-ban me-1"></i> This comment was deleted.</div>
                                                <?php else: ?>
                                                    <p class="mb-0 text-secondary" style="font-size: 1rem; line-height: 1.6;">
                                                        <?php echo nl2br(htmlspecialchars($reply['message'])); ?>
                                                        <?php if($reply['is_edited']): ?>
                                                            <small class="text-muted ms-1" style="font-size: 0.8rem; opacity: 0.7;">(edited)</small>
                                                        <?php endif; ?>
                                                    </p>
                                                    
                                                    <?php if($is_my_reply): ?>
                                                    <div class="mt-3 text-end action-links">
                                                        <button type="button" class="btn btn-sm text-primary fw-bold text-decoration-none p-0 me-3" onclick="openEditReplyModal(<?php echo $reply['id']; ?>, '<?php echo htmlspecialchars(addslashes($reply['message'])); ?>')"><i class="fas fa-pen me-1"></i>Edit</button>
                                                        <a href="?delete_reply=<?php echo $reply['id']; ?>" class="btn btn-sm text-danger fw-bold text-decoration-none p-0" onclick="return confirm('Delete this comment?');"><i class="fas fa-trash-alt me-1"></i>Delete</a>
                                                    </div>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php 
                                        endwhile;
                                    else:
                                    ?>
                                        <div class="text-center text-muted fw-semibold py-3 bg-light rounded-3 border">No comments on this announcement yet. Be the first to reply!</div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Add Reply Form -->
                                <?php if(!isset($row['is_deleted']) || !$row['is_deleted']): ?>
                                <form method="POST" class="d-flex align-items-start mt-3">
                                    <input type="hidden" name="announcement_id" value="<?php echo $row['id']; ?>">
                                    <?php 
                                        $my_img = isset($_SESSION['profile_image']) && !empty($_SESSION['profile_image']) ? '../assets/img/'.$_SESSION['profile_image'] : 'https://ui-avatars.com/api/?name='.urlencode($_SESSION['user_name']).'&background=random';
                                    ?>
                                    <img src="<?php echo $my_img; ?>" class="rounded-circle me-3 mt-1 shadow-sm border" width="50" height="50" alt="Me">
                                    <div class="flex-grow-1 position-relative">
                                        <textarea name="reply_message" class="form-control bg-light border-0 rounded-4 px-4 py-3 pe-5 fs-6 shadow-sm" rows="2" placeholder="Write a comment..." required style="resize:none; padding-right: 60px !important;"></textarea>
                                        <button type="submit" name="add_reply" class="btn btn-primary rounded-circle position-absolute shadow-sm" style="right: 12px; bottom: 12px; width: 44px; height: 44px; padding: 0; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-paper-plane fa-lg"></i>
                                        </button>
                                    </div>
                                </form>
                                <?php endif; ?>
                            </div>
                            <?php endif; // End allow_comments check ?>
                        </div>
                    </div>
                <?php 
                    endwhile;
                } else {
                    echo "<div class='text-center p-5 mt-5 bg-white shadow-sm rounded-4 w-100'>";
                    echo "<i class='fas fa-inbox fa-4x text-muted opacity-25 mb-4'></i>";
                    echo "<h3 class='text-secondary fw-semibold'>No Announcements Right Now</h3>";
                    echo "<p class='text-muted'>Check back later for important updates and news.</p>";
                    echo "</div>";
                }
                ?>
                </div>
            </div>
            
        </div>
    </div>
</div>

<!-- Edit Reply Modal -->
<div class="modal fade" id="editReplyModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header bg-light border-0">
        <h5 class="modal-title fw-bold"><i class="fas fa-pen me-2 text-primary"></i>Edit Comment</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="reply_id" id="edit_reply_id">
        <div class="modal-body p-4">
          <textarea name="reply_message" id="edit_reply_content" class="form-control bg-light border-0 rounded-3" rows="3" required></textarea>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="edit_reply" class="btn btn-primary rounded-pill px-4">Update Comment</button>
        </div>
      </form>
    </div>
  </div>
</div>

<style>
.rich-text-content img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin-top: 10px;
}
.btn-light.border {
    background-color: #f8f9fa;
    border-color: #ddd !important;
}
.btn-light.border:hover {
    background-color: #e9ecef;
}
</style>
<script>
    function openEditReplyModal(id, content) {
        document.getElementById('edit_reply_id').value = id;
        document.getElementById('edit_reply_content').value = content;
        var myModal = new bootstrap.Modal(document.getElementById('editReplyModal'));
        myModal.show();
    }
</script>

<?php include '../includes/footer.php'; ?>
