<?php
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$admin_id = $_SESSION['user_id'];

if (isset($_POST['add_announcement'])) {
    $title = $conn->real_escape_string($_POST['title']);
    // if using rich text, we receive HTML from textarea
    $message = $conn->real_escape_string($_POST['message']); 
    $target_type = $conn->real_escape_string($_POST['target_type']);
    $target_value = $conn->real_escape_string($_POST['target_value'] ?? '');
    $is_critical = isset($_POST['is_critical']) ? 1 : 0;
    $allow_reactions = isset($_POST['allow_reactions']) ? 1 : 0;
    $allow_comments = isset($_POST['allow_comments']) ? 1 : 0;
    
    // Delivery mocks
    $send_email = isset($_POST['send_email']);
    $send_sms = isset($_POST['send_sms']);
    
    $attachment = '';
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'mp4', 'avi'];
        $ext = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
        if (in_array(strtolower($ext), $allowed)) {
            $attachment = time() . '_' . $_FILES['attachment']['name'];
            move_uploaded_file($_FILES['attachment']['tmp_name'], '../assets/img/' . $attachment);
        }
    }

    $sql = "INSERT INTO announcements (admin_id, title, message, attachment, target_type, target_value, is_critical, allow_reactions, allow_comments) 
            VALUES ($admin_id, '$title', '$message', '$attachment', '$target_type', '$target_value', $is_critical, $allow_reactions, $allow_comments)";
    $conn->query($sql);
    $new_ann_id = $conn->insert_id;
    
    // Notify targeted employees
    $emp_query_str = "SELECT id, email, phone FROM users WHERE role='employee'";
    if ($target_type === 'designation' && !empty($target_value)) {
        $emp_query_str .= " AND designation='$target_value'";
    }
    $emp_query = $conn->query($emp_query_str);
    
    $notifs = [];
    while($emp = $emp_query->fetch_assoc()) {
        $notif_msg = ($is_critical ? "🚨 CRITICAL: " : "New Announcement: ") . $title;
        $conn->query("INSERT INTO notifications (user_id, message, link) VALUES ({$emp['id']}, '$notif_msg', 'announcements.php')");
        // Simulated sending
        if ($send_email) { /* mail($emp['email'], ...) */ }
        if ($send_sms && !empty($emp['phone'])) { /* send_sms_api(...) */ }
    }
    
    $msg = "success=1";
    if ($send_email) $msg .= "&email=1";
    if ($send_sms) $msg .= "&sms=1";
    
    header("Location: announcements.php?$msg");
    exit;
}

// Handle Edit Announcement
if (isset($_POST['edit_announcement'])) {
    $id = (int)$_POST['ann_id'];
    $title = $conn->real_escape_string($_POST['title']);
    $message = $conn->real_escape_string($_POST['message']);
    
    $conn->query("UPDATE announcements SET title='$title', message='$message', is_edited=1 WHERE id=$id AND admin_id=$admin_id");
    header("Location: announcements.php?edited=1");
    exit;
}

// Handle Add Reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_reply'])) {
    $ann_id = (int)$_POST['announcement_id'];
    $reply_msg = $conn->real_escape_string(trim($_POST['reply_message']));
    if (!empty($reply_msg)) {
        $conn->query("INSERT INTO announcement_replies (announcement_id, user_id, message) VALUES ($ann_id, $admin_id, '$reply_msg')");
        header("Location: announcements.php");
        exit;
    }
}

// Handle Edit Reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_reply'])) {
    $reply_id = (int)$_POST['reply_id'];
    $new_msg = $conn->real_escape_string(trim($_POST['reply_message']));
    if (!empty($new_msg)) {
        $conn->query("UPDATE announcement_replies SET message='$new_msg', is_edited=1 WHERE id=$reply_id AND user_id=$admin_id");
        header("Location: announcements.php");
        exit;
    }
}

// Handle Delete Reply (Admin can delete anyone's reply)
if (isset($_GET['delete_reply'])) {
    $reply_id = (int)$_GET['delete_reply'];
    $conn->query("UPDATE announcement_replies SET is_deleted=1 WHERE id=$reply_id");
    header("Location: announcements.php");
    exit;
}

include '../includes/header.php';
?>
<div class="page-container">
    <?php include '../includes/sidebar_admin.php'; ?>
    <div class="main-content">
        <div class="container-xxl py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1 fw-bold text-dark"><i class="fas fa-bullhorn me-2 text-primary"></i>Company Announcements</h2>
                    <p class="text-muted mb-0 fs-5">Broadcast messages to all employees directly</p>
                </div>
                <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addAnnouncementModal">
                    <i class="fas fa-plus me-2"></i>New Announcement
                </button>
            </div>
            
            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success rounded-4 border-0 shadow-sm"><i class="fas fa-check-circle me-2"></i>Announcement posted successfully! 
                <?php if(isset($_GET['email'])) echo "<span class='badge bg-light text-success ms-2'>Email Sent</span>"; ?>
                <?php if(isset($_GET['sms'])) echo "<span class='badge bg-light text-success ms-2'>SMS Sent</span>"; ?>
                </div>
            <?php endif; ?>
            <?php if(isset($_GET['edited'])): ?>
                <div class="alert alert-info rounded-4 border-0 shadow-sm"><i class="fas fa-edit me-2"></i>Announcement updated successfully!</div>
            <?php endif; ?>
            <?php if(isset($_GET['deleted'])): ?>
                <div class="alert alert-danger rounded-4 border-0 shadow-sm"><i class="fas fa-trash-alt me-2"></i>Announcement deleted!</div>
            <?php endif; ?>
            
            <div class="row justify-content-center">
                <div class="col-lg-10">
                <?php
                $res = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC");
                if ($res && $res->num_rows > 0) {
                    while ($row = $res->fetch_assoc()):
                ?>
                    <div class="card border-0 shadow-sm rounded-4 mb-4" style="background-color: #ffffff; border-left: 6px solid var(--primary-color) !important; overflow: hidden;">
                        <div class="card-body p-5 position-relative">
                            <span class="position-absolute top-0 end-0 mt-4 me-4 badge bg-light text-muted border px-3 py-2 rounded-pill fs-6 shadow-sm">
                                <i class="far fa-clock me-1"></i><?php echo date('M d, Y', strtotime($row['created_at'])); ?> <span class="ms-1" style="opacity:0.6"><?php echo date('h:i A', strtotime($row['created_at'])); ?></span>
                            </span>
                            
                            <div class="mb-2 d-flex gap-2 align-items-center flex-wrap">
                                <span class="badge bg-primary px-3 py-2 rounded-pill shadow-sm"><i class="fas fa-info-circle me-1"></i>Official Announcement</span>
                                <?php if($row['target_type'] !== 'all'): ?>
                                    <span class="badge bg-secondary px-3 py-2 rounded-pill shadow-sm"><i class="fas fa-users me-1"></i>Target: <?php echo htmlspecialchars($row['target_value']); ?></span>
                                <?php endif; ?>
                                <?php if($row['is_critical']): ?>
                                    <span class="badge bg-danger px-3 py-2 rounded-pill shadow-sm"><i class="fas fa-exclamation-triangle me-1"></i>Critical (Read Receipt Required)</span>
                                <?php endif; ?>
                            </div>

                            <?php if(isset($row['is_deleted']) && $row['is_deleted']): ?>
                                <h3 class="fw-bold text-dark mt-3 mb-3 text-decoration-line-through text-muted" style="letter-spacing: -0.5px;"><?php echo htmlspecialchars($row['title']); ?></h3>
                                <div class="text-danger fs-5 mb-4" style="font-style:italic;"><i class="fas fa-ban me-1"></i> This announcement was deleted.</div>
                            <?php else: ?>
                                <h3 class="fw-bold text-dark mt-3 mb-3" style="letter-spacing: -0.5px;">
                                    <?php echo htmlspecialchars($row['title']); ?>
                                </h3>
                                <div class="text-secondary fs-5 rich-text-content" style="line-height: 1.8;">
                                    <?php echo ($row['message']); // Allowing HTML for rich text ?>
                                    <?php if(isset($row['is_edited']) && $row['is_edited']): ?>
                                        <small class="text-muted ms-1" style="opacity:0.7; font-size: 0.85rem;">(edited)</small>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($row['attachment']): ?>
                                <div class="mt-4 pt-3">
                                    <?php 
                                        $ext = strtolower(pathinfo($row['attachment'], PATHINFO_EXTENSION));
                                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])): 
                                    ?>
                                        <img src="../assets/img/<?php echo $row['attachment']; ?>" class="img-fluid rounded-4 shadow-sm" style="max-height: 400px; object-fit: cover;" alt="Attachment">
                                    <?php elseif (in_array($ext, ['mp4', 'webm'])): ?>
                                        <video controls class="w-100 rounded-4 shadow-sm" style="max-height: 400px;">
                                            <source src="../assets/img/<?php echo $row['attachment']; ?>" type="video/<?php echo $ext; ?>">
                                            Your browser does not support the video tag.
                                        </video>
                                    <?php else: ?>
                                        <a href="../assets/img/<?php echo $row['attachment']; ?>" target="_blank" class="btn btn-outline-danger rounded-pill px-4 fw-bold">
                                            <i class="fas fa-file-alt me-2"></i>Download Attachment
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <!-- Replies Section -->
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
                                                    <div class="text-muted" style="font-style:italic;"><i class="fas fa-ban me-1"></i> This comment was deleted by an admin.</div>
                                                <?php else: ?>
                                                    <p class="mb-0 text-secondary" style="font-size: 1rem; line-height: 1.6;">
                                                        <?php echo nl2br(htmlspecialchars($reply['message'])); ?>
                                                        <?php if($reply['is_edited']): ?>
                                                            <small class="text-muted ms-1" style="font-size: 0.8rem; opacity: 0.7;">(edited)</small>
                                                        <?php endif; ?>
                                                    </p>
                                                    
                                                    <div class="mt-3 text-end action-links">
                                                        <?php if($is_my_reply): ?>
                                                            <button type="button" class="btn btn-sm text-primary fw-bold text-decoration-none p-0 me-3" onclick="openEditReplyModal(<?php echo $reply['id']; ?>, '<?php echo htmlspecialchars(addslashes($reply['message'])); ?>')"><i class="fas fa-pen me-1"></i>Edit</button>
                                                        <?php endif; ?>
                                                        <a href="?delete_reply=<?php echo $reply['id']; ?>" class="btn btn-sm text-danger fw-bold text-decoration-none p-0" onclick="return confirm('Delete this comment?');"><i class="fas fa-trash-alt me-1"></i>Delete</a>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php 
                                        endwhile;
                                    else:
                                    ?>
                                        <div class="text-center text-muted fw-semibold py-3 bg-light rounded-3 border">No comments on this announcement yet.</div>
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
                                        <textarea name="reply_message" class="form-control bg-light border-0 rounded-4 px-4 py-3 pe-5 fs-6 shadow-sm" rows="2" placeholder="Write a comment as Admin..." required style="resize:none; padding-right: 60px !important;"></textarea>
                                        <button type="submit" name="add_reply" class="btn btn-primary rounded-circle position-absolute shadow-sm" style="right: 12px; bottom: 12px; width: 44px; height: 44px; padding: 0; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-paper-plane fa-lg"></i>
                                        </button>
                                    </div>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-footer bg-light border-top p-3 d-flex justify-content-end align-items-center">
                            <?php if(!isset($row['is_deleted']) || !$row['is_deleted']): ?>
                                <button type="button" class="btn btn-outline-primary rounded-pill px-4 me-2 fw-bold" onclick="openEditAnnModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['title'])); ?>', '<?php echo htmlspecialchars(addslashes($row['message'])); ?>')">
                                    <i class="fas fa-pen me-2"></i>Edit
                                </button>
                                <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-outline-danger rounded-pill px-4 fw-bold" onclick="return confirm('Are you sure you want to delete this announcement?');">
                                    <i class="fas fa-trash-alt me-2"></i>Delete
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php 
                    endwhile;
                } else {
                    echo "<div class='text-center p-5 mt-4 bg-white shadow-sm rounded-4 w-100' style='border-left: 5px solid var(--primary-color);'>";
                    echo "<i class='fas fa-bullhorn fa-4x text-muted opacity-25 mb-4'></i>";
                    echo "<h3 class='text-dark fw-bold'>No Announcements Yet</h3>";
                    echo "<p class='text-muted fs-5'>Post an announcement to notify employees.</p>";
                    echo "</div>";
                }
                ?>
                </div>
            
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="addAnnouncementModal" tabindex="-1" aria-labelledby="addAnnouncementModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header bg-light border-0">
        <h5 class="modal-title fw-bold" id="addAnnouncementModalLabel"><i class="fas fa-bullhorn me-2 text-primary"></i>Post Announcement</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" enctype="multipart/form-data">
          <div class="modal-body p-4">
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="form-label fw-bold">Title</label>
                    <input type="text" name="title" class="form-control form-control-lg bg-light border-0" required placeholder="Enter announcement title">
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Target Segment</label>
                    <select name="target_type" id="target_type" class="form-select bg-light border-0" onchange="toggleTargetValue()">
                        <option value="all">All Employees</option>
                        <option value="designation">Specific Designation / Role</option>
                    </select>
                </div>
                <div class="col-md-6" id="target_value_container" style="display:none;">
                    <label class="form-label fw-bold">Select Designation</label>
                    <select name="target_value" class="form-select bg-light border-0">
                        <?php
                            $desigs = $conn->query("SELECT DISTINCT designation FROM users WHERE designation IS NOT NULL AND designation != ''");
                            while($d = $desigs->fetch_assoc()) {
                                echo "<option value='".htmlspecialchars($d['designation'])."'>".htmlspecialchars($d['designation'])."</option>";
                            }
                        ?>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Message <small class="text-muted fw-normal">(Rich Text Supported)</small></label>
                <textarea name="message" id="editor_message" class="form-control bg-light border-0" rows="5" placeholder="Enter the announcement message..."></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">Media / Attachment <small class="text-muted fw-normal">(Images, Videos, PDFs)</small></label>
                <input type="file" name="attachment" class="form-control bg-light border-0" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.mp4,.avi">
            </div>
            
            <div class="card bg-light border-0 rounded-4 p-3 mt-4">
                <h6 class="fw-bold mb-3"><i class="fas fa-cogs me-2"></i>Advanced Options</h6>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" role="switch" name="is_critical" value="1" id="switchCritical">
                            <label class="form-check-label fw-semibold" for="switchCritical">Critical (Require Read Receipt)</label>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" role="switch" name="allow_reactions" value="1" id="switchReact" checked>
                            <label class="form-check-label fw-semibold" for="switchReact">Allow Reactions (👍, ❤️)</label>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" role="switch" name="allow_comments" value="1" id="switchComment" checked>
                            <label class="form-check-label fw-semibold" for="switchComment">Allow Comments / Replies</label>
                        </div>
                    </div>
                    <div class="col-md-6 border-start">
                        <p class="mb-2 fw-semibold text-muted fs-6">Multi-Channel Delivery</p>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="send_email" value="1" id="checkEmail" checked>
                            <label class="form-check-label" for="checkEmail"><i class="fas fa-envelope me-2 text-primary"></i>Send via Email Digest</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="send_sms" value="1" id="checkSMS">
                            <label class="form-check-label" for="checkSMS"><i class="fas fa-sms me-2 text-success"></i>Send Urgent SMS</label>
                        </div>
                    </div>
                </div>
            </div>

          </div>
          <div class="modal-footer border-0">
            <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="add_announcement" class="btn btn-primary rounded-pill px-4">Post Announcement</button>
          </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Announcement Modal -->
<div class="modal fade" id="editAnnouncementModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header bg-light border-0">
        <h5 class="modal-title fw-bold"><i class="fas fa-pen me-2 text-primary"></i>Edit Announcement</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST">
          <input type="hidden" name="ann_id" id="edit_ann_id">
          <div class="modal-body p-4">
            <div class="mb-3">
                <label class="form-label fw-bold">Title</label>
                <input type="text" name="title" id="edit_ann_title" class="form-control form-control-lg bg-light border-0" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">Message</label>
                <textarea name="message" id="edit_ann_message" class="form-control bg-light border-0" rows="5" required></textarea>
            </div>
            <div class="alert alert-warning border-0 rounded-3" style="font-size: 0.85rem;">
                <i class="fas fa-info-circle me-1"></i> Attachments cannot be changed during edits.
            </div>
          </div>
          <div class="modal-footer border-0">
            <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="edit_announcement" class="btn btn-primary rounded-pill px-4">Update Announcement</button>
          </div>
      </form>
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

<script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
<script>
    function openEditAnnModal(id, title, message) {
        document.getElementById('edit_ann_id').value = id;
        document.getElementById('edit_ann_title').value = title;
        document.getElementById('edit_ann_message').value = message;
        var myModal = new bootstrap.Modal(document.getElementById('editAnnouncementModal'));
        myModal.show();
    }

    function openEditReplyModal(id, content) {
        document.getElementById('edit_reply_id').value = id;
        document.getElementById('edit_reply_content').value = content;
        var myModal = new bootstrap.Modal(document.getElementById('editReplyModal'));
        myModal.show();
    }
    
    function toggleTargetValue() {
        var type = document.getElementById('target_type').value;
        document.getElementById('target_value_container').style.display = (type === 'designation') ? 'block' : 'none';
    }

    ClassicEditor
        .create( document.querySelector( '#editor_message' ) , {
            toolbar: [ 'heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote' ]
        })
        .catch( error => {
            console.error( error );
        } );
</script>

<style>
.ck-editor__editable_inline {
    min-height: 150px;
}
.rich-text-content img {
    max-width: 100%;
    height: auto;
}
</style>

<?php include '../includes/footer.php'; ?>
