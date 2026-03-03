<?php
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'employee') {
    header('Location: ../auth/login.php');
    exit;
}
$current_user_id = $_SESSION['user_id'];
$chat_type = isset($_GET['type']) ? $_GET['type'] : 'user';
$active_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!function_exists('getUserColor')) {
    function getUserColor($name) {
        // Use variations of the single accent color
        $colors = ['#1058d0', '#0b4aa8', '#0f172a'];
        $hash = abs(crc32($name));
        return $colors[$hash % count($colors)];
    }
}

// Handle message send
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['chat_type'])) {
    $message = trim($_POST['message']);
    $type = $_POST['chat_type'];
    $target_id = intval($_POST['target_id']);
    
    $attachment = null;
    if (isset($_FILES['chat_file']) && $_FILES['chat_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../assets/uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $file_name = time() . '_chat_' . basename($_FILES['chat_file']['name']);
        $target_path = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES['chat_file']['tmp_name'], $target_path)) {
            $attachment = $file_name;
        }
    }

    if (!empty($message) || $attachment) {
        if ($type === 'user') {
            $stmt = $conn->prepare("INSERT INTO chats (sender_id, receiver_id, message, attachment) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $current_user_id, $target_id, $message, $attachment);
            $stmt->execute();
        } elseif ($type === 'group') {
            $stmt = $conn->prepare("INSERT INTO chats (sender_id, group_id, message, attachment) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $current_user_id, $target_id, $message, $attachment);
            $stmt->execute();
        }
        header("Location: chat.php?type=$type&id=$target_id");
        exit;
    }
}

// Handle Edit Message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_message'])) {
    $msg_id = intval($_POST['msg_id']);
    $new_msg = trim($_POST['message']);
    $type = isset($_POST['chat_type']) ? $_POST['chat_type'] : 'user';
    $target_id = isset($_POST['target_id']) ? intval($_POST['target_id']) : 0;
    
    $upd = $conn->prepare("UPDATE chats SET message = ?, is_edited = 1 WHERE id = ? AND sender_id = ?");
    $upd->bind_param("sii", $new_msg, $msg_id, $current_user_id);
    $upd->execute();
    header("Location: chat.php?type=$type&id=$target_id");
    exit;
}

// Handle Delete Message
if (isset($_GET['delete_msg'])) {
    $msg_id = intval($_GET['delete_msg']);
    $type = isset($_GET['type']) ? $_GET['type'] : 'user';
    $target_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    $del = $conn->prepare("UPDATE chats SET is_deleted = 1 WHERE id = ? AND sender_id = ?");
    $del->bind_param("ii", $msg_id, $current_user_id);
    $del->execute();
    header("Location: chat.php?type=$type&id=$target_id");
    exit;
}

include '../includes/header.php';
?>
<style>
/* Modern Chat UI mimicking the provided design */
body {
    background-color: #f4f6f9; /* Soft background */
}
.page-container {
    padding-bottom: 0;
}
.chat-app-wrapper {
    display: flex;
    height: calc(100vh - 80px); /* Adjust based on navbar height */
    background: #ffffff;
    border-radius: 0;
    margin: -1.5rem; /* Negate the container padding for full width inside */
    box-shadow: 0 5px 20px rgba(0,0,0,0.03);
    overflow: hidden;
}

/* Sidebar styling */
.chat-sidebar {
    width: 340px;
    background: #ffffff;
    border-right: 1px solid #eef2f5;
    display: flex;
    flex-direction: column;
}

.chat-sidebar-header {
    padding: 25px 25px 15px;
    border-bottom: 1px solid transparent;
}

.chat-sidebar-header h3 {
    font-weight: 700;
    margin-bottom: 20px;
    color: #1a1b1e;
    font-size: 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chat-sidebar-header h3 i {
    color: #9cb1c5;
    font-size: 1.2rem;
    cursor: pointer;
}

.search-container {
    background: #f8f9fa;
    border-radius: 20px;
    padding: 10px 15px;
    display: flex;
    align-items: center;
    border: 1px solid #eef2f5;
    transition: all 0.3s ease;
}
.search-container:focus-within {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.15rem rgba(16,88,208,.15);
}
.search-container input {
    border: none;
    background: transparent;
    outline: none;
    width: 100%;
    margin-left: 10px;
    font-size: 0.95rem;
    color: #495057;
}

.chat-list {
    flex: 1;
    overflow-y: auto;
    padding: 10px 0;
}
.chat-list::-webkit-scrollbar {
    width: 6px;
}
.chat-list::-webkit-scrollbar-thumb {
    background-color: #e2e8f0;
    border-radius: 3px;
}

.chat-list-section {
    padding: 15px 25px 5px;
    font-size: 0.8rem;
    color: #64748b;
    font-weight: 600;
}

.chat-item {
    display: flex;
    align-items: center;
    padding: 12px 25px;
    cursor: pointer;
    transition: background 0.2s;
    text-decoration: none !important;
    color: inherit;
    position: relative;
}

.chat-item:hover {
    background: #f8fafc;
}

.chat-item.active {
    background: #f8fafc;
}

.chat-avatar-wrapper {
    position: relative;
    margin-right: 15px;
}

.chat-avatar {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    object-fit: cover;
    background-color: #e9ecef;
}

.status-check {
    width: 16px;
    height: 16px;
    background-color: var(--primary-color);
    color: white;
    border-radius: 50%;
    border: 2px solid white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 8px;
    position: absolute;
    bottom: -2px;
    right: -2px;
}

.chat-item-content {
    flex: 1;
    min-width: 0;
}

.chat-item-title {
    font-weight: 600;
    color: #1a1b1e;
    margin-bottom: 2px;
    display: flex;
    justify-content: space-between;
    font-size: 0.95rem;
}

.chat-item-time {
    font-size: 0.75rem;
    color: #94a3b8;
    font-weight: 500;
}

.chat-item-subtitle {
    font-size: 0.85rem;
    color: #64748b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: flex;
    align-items: center;
}

.unread-badge {
    background: var(--primary-color);
    color: white;
    font-size: 0.7rem;
    height: 18px;
    min-width: 18px;
    border-radius: 9px;
    padding: 0 5px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-left: 5px;
}

/* Main Chat styling */
.chat-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: #f8fafc;
}

.chat-header {
    background: #ffffff;
    padding: 15px 30px;
    border-bottom: 1px solid #eef2f5;
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 80px;
}

.chat-header-info {
    display: flex;
    align-items: center;
}

.chat-header-title {
    font-weight: 700;
    font-size: 1.15rem;
    color: #0f172a;
    margin-bottom: 2px;
    display: flex;
    align-items: center;
}

.chat-header-status {
    font-size: 0.85rem;
    color: var(--primary-color);
    font-style: italic;
}

.chat-header-actions button {
    background: transparent;
    border: none;
    color: #64748b;
    font-size: 1.25rem;
    margin-left: 20px;
    cursor: pointer;
    transition: color 0.2s;
}
.chat-header-actions button:hover {
    color: #0f172a;
}

.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 30px;
    display: flex;
    flex-direction: column;
}

.message-date {
    text-align: center;
    margin: 15px 0 30px;
}
.message-date span {
    background: #ffffff;
    padding: 6px 16px;
    border-radius: 20px;
    font-size: 0.8rem;
    color: #64748b;
    font-weight: 500;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.message-wrapper {
    display: flex;
    margin-bottom: 25px;
    align-items: flex-end; /* avatars at bottom */
}

.message-wrapper.me {
    justify-content: flex-end;
}

.message-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    margin: 0 12px;
    flex-shrink: 0;
}

.message-content {
    max-width: 65%;
    display: flex;
    flex-direction: column;
}

.message-wrapper.me .message-content {
    align-items: flex-end;
}

.message-bubble {
    padding: 16px 20px;
    border-radius: 12px;
    font-size: 0.95rem;
    box-shadow: 0 2px 5px rgba(0,0,0,0.02);
    position: relative;
    line-height: 1.5;
    word-wrap: break-word;
}

.message-wrapper.them .message-bubble {
    background: #ffffff;
    color: #334155;
    border-bottom-left-radius: 4px;
}

.message-wrapper.me .message-bubble {
    background: #3b82f6; /* modern blue */
    color: #ffffff;
    border-bottom-right-radius: 4px;
}

.message-actions {
    opacity: 0;
    transition: opacity 0.2s;
    display: flex;
    align-items: center;
    margin: 0 5px;
}
.message-wrapper:hover .message-actions {
    opacity: 1;
}
.msg-action-btn {
    background: transparent;
    border: none;
    cursor: pointer;
    font-size: 0.85rem;
    padding: 3px 6px;
    border-radius: 4px;
}
.message-wrapper.them .msg-action-btn { color: #64748b; }
.message-wrapper.them .msg-action-btn:hover { background: #f1f5f9; color: #0f172a; }

.message-wrapper.me .msg-action-btn { color: rgba(255,255,255,0.7); }
.message-wrapper.me .msg-action-btn:hover { background: rgba(255,255,255,0.2); color: #fff; }

.message-sender {
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 6px;
}

.message-time {
    font-size: 0.75rem;
    color: #94a3b8;
    margin-top: 6px;
    padding: 0 12px;
}

/* Chat Input Styling */
.chat-input-area {
    background: #f8fafc;
    padding: 15px 30px 30px;
}

.chat-input-wrapper {
    background: #ffffff;
    border-radius: 16px;
    padding: 8px 15px;
    display: flex;
    align-items: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.02);
    border: 1px solid #e2e8f0;
}

.chat-input {
    flex: 1;
    border: none;
    background: transparent;
    padding: 12px 15px;
    font-size: 1rem;
    outline: none;
    color: #334155;
}

.chat-input::placeholder {
    color: #94a3b8;
}

.chat-action-btn {
    background: transparent;
    border: none;
    color: #64748b;
    font-size: 1.2rem;
    padding: 10px;
    cursor: pointer;
    transition: color 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.chat-action-btn:hover {
    color: #3b82f6;
}

.chat-send-btn {
    background: transparent;
    color: var(--primary-color);
    border: none;
    font-size: 1.3rem;
    padding: 10px 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: color 0.2s;
}

.chat-send-btn:hover {
    color: #0b4aa8;
}

.attachment-preview {
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 10px;
    margin-top: 10px;
    display: flex;
    align-items: center;
    max-width: 250px;
}
.attachment-preview.me {
    background: rgba(255,255,255,0.1);
    border-color: rgba(255,255,255,0.2);
}

@media (max-width: 768px) {
    .chat-sidebar {
        width: 100%;
        display: <?php echo $active_id > 0 ? 'none' : 'flex'; ?>;
    }
    .chat-main {
        display: <?php echo $active_id > 0 ? 'flex' : 'none'; ?>;
    }
}
</style>

<div class="page-container">
    <?php include '../includes/sidebar_user.php'; ?>
    <div class="main-content">
        <div class="container-fluid p-0">
            <div class="chat-app-wrapper">
                
                <!-- Sidebar -->
                <div class="chat-sidebar">
                    <div class="chat-sidebar-header">
                        <h3>Messages <i class="far fa-edit"></i></h3>
                        <div class="search-container">
                            <i class="fas fa-search text-muted"></i>
                            <input type="text" placeholder="Search">
                        </div>
                    </div>
                    
                    <div class="chat-list">
                        <!-- Pinned Section -->
                        <div class="chat-list-section d-flex align-items-center mb-2">
                            <i class="fas fa-thumbtack me-2" style="transform: rotate(45deg); font-size: 0.85rem;"></i> Pinned
                        </div>
                        <?php
                        $admins = $conn->query("SELECT * FROM users WHERE role='admin' ORDER BY name ASC limit 3");
                        if ($admins) {
                            while ($u = $admins->fetch_assoc()) {
                                $active = ($chat_type === 'user' && $active_id == $u['id']) ? 'active' : '';
                                $avatar_url = "https://ui-avatars.com/api/?name=".urlencode($u['name'])."&background=1e293b&color=fff";
                                
                                $unread = 0;
                                try {
                                    $unread_q = $conn->query("SELECT COUNT(*) as unread FROM chats WHERE sender_id = {$u['id']} AND receiver_id = $current_user_id AND is_read = 0");
                                    if ($unread_q) {
                                        $unread = $unread_q->fetch_assoc()['unread'];
                                    }
                                } catch (Exception $e) {}
                                
                                echo '<a href="chat.php?type=user&id='.$u['id'].'" class="chat-item '.$active.'">';
                                echo '  <div class="chat-avatar-wrapper">';
                                echo '      <img src="'.$avatar_url.'" class="chat-avatar" alt="Avatar">';
                                echo '      <div class="status-check bg-danger border-white"><i class="fas fa-shield-alt text-white" style="font-size:0.4rem;"></i></div>'; // Admin badge
                                echo '  </div>';
                                echo '  <div class="chat-item-content">';
                                echo '      <div class="chat-item-title">';
                                echo '          <span>' . htmlspecialchars($u['name']) . '</span>';
                                echo '          <span class="chat-item-time">4m</span>';
                                echo '      </div>';
                                echo '      <div class="chat-item-subtitle">';
                                echo '          <span class="text-truncate">Admin Contact</span>';
                                if ($unread > 0) echo '<span class="unread-badge">'.$unread.'</span>';
                                echo '      </div>';
                                echo '  </div>';
                                echo '</a>';
                            }
                        }
                        ?>

                        <!-- All Messages -->
                        <div class="chat-list-section d-flex align-items-center mt-3 mb-2">
                            <i class="far fa-comment-dots me-2"></i> All Messages
                        </div>
                        <?php
                        // Groups
                        $groups = $conn->query("SELECT g.* FROM chat_groups g JOIN chat_group_members m ON g.id = m.group_id WHERE m.user_id = $current_user_id ORDER BY g.created_at DESC");
                        if ($groups) {
                            while ($g = $groups->fetch_assoc()) {
                                $active = ($chat_type === 'group' && $active_id == $g['id']) ? 'active' : '';
                                $avatar_url = "https://ui-avatars.com/api/?name=".urlencode($g['name'])."&background=f59e0b&color=fff";
                                
                                echo '<a href="chat.php?type=group&id='.$g['id'].'" class="chat-item '.$active.'">';
                                echo '  <div class="chat-avatar-wrapper">';
                                echo '      <img src="'.$avatar_url.'" class="chat-avatar" alt="Group Avatar" style="border-radius: 12px;">';
                                echo '  </div>';
                                echo '  <div class="chat-item-content">';
                                echo '      <div class="chat-item-title"><span>' . htmlspecialchars($g['name']) . '</span><span class="chat-item-time">1h</span></div>';
                                echo '      <div class="chat-item-subtitle">';
                                echo '          <span class="text-truncate">Group Chat</span>';
                                echo '      </div>';
                                echo '  </div>';
                                echo '</a>';
                            }
                        }

                        // Users
                        $users = $conn->query("SELECT * FROM users WHERE id != $current_user_id AND role!='admin' ORDER BY name ASC");
                        if ($users) {
                            while ($u = $users->fetch_assoc()) {
                                $active = ($chat_type === 'user' && $active_id == $u['id']) ? 'active' : '';
                                $color = getUserColor($u['name']);
                                $avatar_url = "https://ui-avatars.com/api/?name=".urlencode($u['name'])."&background=".substr($color,1)."&color=fff";
                                
                                $unread = 0;
                                try {
                                    $unread_q = $conn->query("SELECT COUNT(*) as unread FROM chats WHERE sender_id = {$u['id']} AND receiver_id = $current_user_id AND is_read = 0");
                                    if ($unread_q) {
                                        $unread = $unread_q->fetch_assoc()['unread'];
                                    }
                                } catch (Exception $e) {}
                                
                                echo '<a href="chat.php?type=user&id='.$u['id'].'" class="chat-item '.$active.'">';
                                echo '  <div class="chat-avatar-wrapper">';
                                echo '      <img src="'.$avatar_url.'" class="chat-avatar" alt="Avatar">';
                                if ($unread == 0) echo '      <div class="status-check"><i class="fas fa-check text-white"></i></div>';
                                echo '  </div>';
                                echo '  <div class="chat-item-content">';
                                echo '      <div class="chat-item-title">';
                                echo '          <span>' . htmlspecialchars($u['name']) . '</span>';
                                echo '          <span class="chat-item-time">'. rand(2, 59) .'m</span>';
                                echo '      </div>';
                                echo '      <div class="chat-item-subtitle">';
                                echo '          <span class="text-truncate" style="'.($unread>0?'color:#0f172a;font-weight:600;':'').'">Tap to view chat...</span>';
                                if ($unread > 0) {
                                    echo '<span class="unread-badge">'.$unread.'</span>';
                                } else {
                                    echo '<i class="fas fa-check-double text-blue ms-auto" style="color: #3b82f6;"></i>';
                                }
                                echo '      </div>';
                                echo '  </div>';
                                echo '</a>';
                            }
                        }
                        ?>
                    </div>
                </div>
                
                <!-- Main Chat Window -->
                <div class="chat-main">
                    <?php if ($active_id > 0): 
                        $chat_name = "Unknown";
                        $header_avatar = "https://ui-avatars.com/api/?name=U&background=1e293b&color=fff";
                        if ($chat_type === 'user') {
                            $u_info = $conn->query("SELECT name FROM users WHERE id = $active_id")->fetch_assoc();
                            if ($u_info) {
                                $chat_name = $u_info['name'];
                                $header_avatar = "https://ui-avatars.com/api/?name=".urlencode($chat_name)."&background=1e293b&color=fff";
                            }
                        } else {
                            $g_info = $conn->query("SELECT name FROM chat_groups WHERE id = $active_id")->fetch_assoc();
                            if ($g_info) {
                                $chat_name = $g_info['name'];
                                $header_avatar = "https://ui-avatars.com/api/?name=".urlencode($chat_name)."&background=f59e0b&color=fff";
                            }
                        }
                    ?>
                    
                    <!-- Chat Header -->
                    <div class="chat-header">
                        <div class="chat-header-info">
                            <?php if ($chat_type === 'user'): ?>
                            <a href="chat.php" class="d-md-none me-3 text-secondary" style="font-size: 1.2rem;"><i class="fas fa-arrow-left"></i></a>
                            <?php endif; ?>
                            <div class="chat-avatar-wrapper">
                                <img src="<?php echo $header_avatar; ?>" class="chat-avatar me-3" style="width: 48px; height: 48px; border-radius: <?php echo $chat_type=='group'?'12px':'50%'; ?>;">
                            </div>
                            <div>
                                <h5 class="chat-header-title"><?php echo htmlspecialchars($chat_name); ?></h5>
                                <span class="chat-header-status text-success"><?php echo $chat_type==='user' ? 'Online' : 'Group Members Active'; ?></span>
                            </div>
                        </div>
                        <div class="chat-header-actions">
                            <button title="Video Call"><i class="fas fa-video"></i></button>
                            <button title="Voice Call"><i class="fas fa-phone-alt"></i></button>
                            <button title="More Options"><i class="fas fa-ellipsis-h"></i></button>
                        </div>
                    </div>

                    <!-- Chat Messages -->
                    <div class="chat-messages" id="chatbox">
                        <div class="message-date">
                            <span>Today, <?php echo date('M d'); ?></span>
                        </div>
                        <?php
                        if ($chat_type === 'user') {
                            try { $conn->query("UPDATE chats SET is_read = 1 WHERE sender_id = $active_id AND receiver_id = $current_user_id"); } catch (Exception $e) {}
                            $chats = $conn->query("SELECT c.*, u.name as sender_name FROM chats c JOIN users u ON c.sender_id = u.id WHERE (c.sender_id = $current_user_id AND c.receiver_id = $active_id) OR (c.sender_id = $active_id AND c.receiver_id = $current_user_id) ORDER BY c.created_at ASC");
                        } else {
                            $chats = $conn->query("SELECT c.*, u.name as sender_name FROM chats c JOIN users u ON c.sender_id = u.id WHERE c.group_id = $active_id ORDER BY c.created_at ASC");
                        }
                        
                        if (!$chats || $chats->num_rows == 0) {
                            echo '<div class="text-center text-muted my-4">No messages yet. Send a message to start the conversation!</div>';
                        }
                        
                        if ($chats) {
                            while ($chat = $chats->fetch_assoc()):
                                $is_me = ($chat['sender_id'] == $current_user_id);
                                $sender_color = getUserColor($chat['sender_name']);
                                $sender_avatar = "https://ui-avatars.com/api/?name=".urlencode($chat['sender_name'])."&background=".substr($sender_color,1)."&color=fff";
                            ?>
                                <div class="message-wrapper <?php echo $is_me ? 'me' : 'them'; ?>">
                                    <?php if (!$is_me): ?>
                                        <img src="<?php echo $sender_avatar; ?>" class="message-avatar">
                                    <?php endif; ?>
                                    
                                    <div class="message-content">
                                        <div class="message-bubble">
                                            <?php if (!$is_me): ?>
                                                <div class="message-sender" style="color: <?php echo $sender_color; ?>;">
                                                    <?php echo htmlspecialchars($chat['sender_name']); ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="message-sender" style="color: #bfdbfe;">
                                                    You
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if(isset($chat['is_deleted']) && $chat['is_deleted']): ?>
                                                <div class="mb-0" style="font-style:italic; opacity:0.8;">
                                                    <i class="fas fa-ban me-1"></i> This message was deleted.
                                                </div>
                                            <?php else: ?>
                                                <div class="mb-0" style="margin-top: <?php echo $is_me ? '-5px' : '0'; ?>;">
                                                    <?php echo nl2br(htmlspecialchars($chat['message'] ?? '')); ?>
                                                    <?php if(isset($chat['is_edited']) && $chat['is_edited']): ?>
                                                        <small style="opacity:0.7;font-size:0.7rem;" class="ms-1">(edited)</small>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <?php if($chat['attachment']): ?>
                                                    <div class="attachment-preview mt-2 <?php echo $is_me ? 'me' : ''; ?>">
                                                        <div class="bg-light rounded p-2 me-2 text-primary d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                                            <i class="fas fa-file-alt"></i>
                                                        </div>
                                                        <div class="me-3 flex-grow-1 overflow-hidden">
                                                            <div class="fw-bold text-truncate <?php echo $is_me ? 'text-white' : 'text-dark'; ?>" style="font-size: 0.85rem;" title="<?php echo htmlspecialchars(basename($chat['attachment'])); ?>">
                                                                <?php echo htmlspecialchars(basename($chat['attachment'])); ?>
                                                            </div>
                                                        </div>
                                                        <a href="../assets/uploads/<?php echo htmlspecialchars($chat['attachment']); ?>" download target="_blank" class="btn btn-sm btn-light border rounded-circle shadow-sm" style="width:32px;height:32px;padding:0;display:flex;align-items:center;justify-content:center;">
                                                            <i class="fas fa-download text-primary"></i>
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="d-flex align-items-center <?php echo $is_me?'justify-content-end':''; ?>" style="<?php echo $is_me?'margin-right:0px;':''; ?>">
                                            <?php if($is_me && (!isset($chat['is_deleted']) || !$chat['is_deleted'])): ?>
                                                <div class="message-actions">
                                                    <button type="button" class="msg-action-btn edit-msg-btn" onclick="openEditModal(<?php echo $chat['id']; ?>, '<?php echo htmlspecialchars(addslashes($chat['message'] ?? '')); ?>')">
                                                        <i class="fas fa-pen" style="font-size:0.75rem;"></i>
                                                    </button>
                                                    <a href="?type=<?php echo $chat_type; ?>&id=<?php echo $active_id; ?>&delete_msg=<?php echo $chat['id']; ?>" class="msg-action-btn" onclick="return confirm('Delete this message?');">
                                                        <i class="fas fa-trash" style="font-size:0.75rem;"></i>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                            <span class="message-time mb-0" style="padding:0;">
                                                <?php echo date('h:i A', strtotime($chat['created_at'])); ?>
                                                <?php if($is_me && $chat_type === 'user'): ?>
                                                    <?php $has_read = isset($chat['is_read']) ? $chat['is_read'] : 1; ?>
                                                    <i class="fas fa-check-double ms-1" style="color: <?php echo $has_read ? '#3b82f6' : '#94a3b8'; ?>"></i>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <?php if ($is_me): 
                                        $my_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : "Me";
                                        $my_avatar = "https://ui-avatars.com/api/?name=".urlencode($my_name)."&background=ef4444&color=fff";
                                    ?>
                                        <img src="<?php echo $my_avatar; ?>" class="message-avatar">
                                    <?php endif; ?>
                                </div>
                            <?php endwhile; 
                        } ?>
                    </div>

                    <!-- Chat Input -->
                    <div class="chat-input-area">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="target_id" value="<?php echo $active_id; ?>">
                            <input type="hidden" name="chat_type" value="<?php echo $chat_type; ?>">
                            
                            <div class="chat-input-wrapper">
                                <label for="chat_file" class="chat-action-btn mb-0" title="Attach file">
                                    <i class="fas fa-paperclip"></i>
                                </label>
                                <input type="file" name="chat_file" id="chat_file" class="d-none">
                                
                                <input type="text" name="message" class="chat-input" placeholder="Type your message..." autocomplete="off">
                                
                                <button type="button" class="chat-action-btn" title="Emoji"><i class="far fa-smile"></i></button>
                                
                                <button type="submit" name="send_message" class="chat-send-btn">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                            <div id="file_name_display" class="mt-2 ms-3 text-primary fw-bold" style="display:none;font-size:0.8rem;"></div>
                        </form>
                    </div>

                    <?php else: ?>
                    <div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted" style="background: #f8fafc;">
                        <div class="bg-white rounded-circle d-flex justify-content-center align-items-center mb-4 shadow-sm" style="width: 120px; height: 120px;">
                            <img src="https://ui-avatars.com/api/?name=WP&background=1e293b&color=fff" style="width: 60px; height: 60px; border-radius: 50%;">
                        </div>
                        <h4 class="text-slate-800 fw-bold" style="color: #1e293b;">Welcome to Messages</h4>
                        <p style="color: #64748b;">Select a conversation from the sidebar to start chatting</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Message Modal -->
<div class="modal fade" id="editMessageModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <form method="POST">
        <input type="hidden" name="msg_id" id="edit_msg_id">
        <input type="hidden" name="target_id" value="<?php echo $active_id; ?>">
        <input type="hidden" name="chat_type" value="<?php echo $chat_type; ?>">
        <div class="modal-header bg-light border-0">
          <h5 class="modal-title fw-bold"><i class="fas fa-pen me-2 text-primary"></i>Edit Message</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <textarea name="message" id="edit_msg_content" class="form-control bg-light border-0" rows="3" required></textarea>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="edit_message" class="btn btn-primary rounded-pill px-4">Update Message</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
    function openEditModal(id, content) {
        document.getElementById('edit_msg_id').value = id;
        document.getElementById('edit_msg_content').value = content;
        var myModal = new bootstrap.Modal(document.getElementById('editMessageModal'));
        myModal.show();
    }
    // Scroll chat to bottom
    const chatbox = document.getElementById('chatbox');
    if (chatbox) {
        chatbox.scrollTop = chatbox.scrollHeight;
    }

    // Display selected file name
    const fileInput = document.getElementById('chat_file');
    const fileNameDisplay = document.getElementById('file_name_display');
    const messageInput = document.querySelector('input[name="message"]');

    if(fileInput && fileNameDisplay && messageInput) {
        fileInput.addEventListener('change', function() {
            if(this.files && this.files.length > 0) {
                fileNameDisplay.innerHTML = '<i class="fas fa-check-circle me-1 text-success"></i> Attached: ' + this.files[0].name;
                fileNameDisplay.style.display = 'block';
                messageInput.removeAttribute('required');
            } else {
                fileNameDisplay.style.display = 'none';
                messageInput.setAttribute('required', 'required');
            }
        });
    }

    // Simple validation
    const form = document.querySelector('form');
    if (form && messageInput) {
        form.addEventListener('submit', function(e) {
            if(!messageInput.value.trim() && (!fileInput.files || fileInput.files.length === 0)) {
                e.preventDefault();
                messageInput.focus();
            }
        });
    }
</script>

<?php include '../includes/footer.php'; ?>
