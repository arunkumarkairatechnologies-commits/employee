<?php
// admin/hub.php - Social Hub for Admins
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$admin_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle Create Event
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_event'])) {
    $title = $conn->real_escape_string(trim($_POST['title']));
    $description = $conn->real_escape_string(trim($_POST['description']));
    $event_date = $conn->real_escape_string($_POST['event_date']);
    $event_type = $conn->real_escape_string($_POST['event_type']);

    if (!empty($title) && !empty($event_date)) {
        $stmt = $conn->prepare("INSERT INTO events (title, description, event_date, event_type, created_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssi', $title, $description, $event_date, $event_type, $admin_id);
        if ($stmt->execute()) {
            $success = "Event successfully created!";
        } else {
            $error = "Failed to create event.";
        }
        $stmt->close();
    } else {
        $error = "Title and Date are required.";
    }
}

// Handle Delete Event
if (isset($_GET['delete_event'])) {
    $del_id = (int)$_GET['delete_event'];
    $conn->query("DELETE FROM events WHERE id = $del_id");
    $conn->query("DELETE FROM event_rsvps WHERE event_id = $del_id");
    header('Location: hub.php?deleted=1');
    exit;
}

if (isset($_GET['deleted'])) {
    $success = "Event deleted successfully.";
}

include '../includes/header.php';
?>

<div class="page-container">
    <?php include '../includes/sidebar_admin.php'; ?>
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4 mt-2">
            <div>
                <h2 class="fw-bold mb-1"><i class="fas fa-calendar-alt text-primary me-2"></i>Social Hub & Events</h2>
                <p class="text-muted fs-5">Manage company culture, events, and celebrations.</p>
            </div>
            <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#createEventModal">
                <i class="fas fa-plus me-2"></i>Create Event
            </button>
        </div>

        <?php if($success): ?>
            <div class="alert alert-success border-0 shadow-sm rounded-4"><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="alert alert-danger border-0 shadow-sm rounded-4"><i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="row">
            <!-- Left Column: Events -->
            <div class="col-lg-8">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold mb-0">Upcoming Events</h4>
                </div>
                
                <?php
                $events_sql = "SELECT e.*, 
                               (SELECT COUNT(*) FROM event_rsvps r WHERE r.event_id=e.id AND r.status='going') as going_count 
                               FROM events e WHERE DATE(e.event_date) >= CURRENT_DATE() ORDER BY e.event_date ASC";
                $events_res = $conn->query($events_sql);
                
                if ($events_res->num_rows > 0):
                    while ($ev = $events_res->fetch_assoc()):
                        // Determine badge color
                        $badge_class = 'bg-primary';
                        if ($ev['event_type'] == 'team_building') $badge_class = 'bg-success';
                        if ($ev['event_type'] == 'social') $badge_class = 'bg-info';
                ?>
                        <div class="card border-0 shadow-sm rounded-4 mb-3 border-start border-4" style="border-left-color: var(--bs-primary) !important;">
                            <div class="card-body p-4 position-relative">
                                <a href="?delete_event=<?php echo $ev['id']; ?>" class="btn btn-sm btn-outline-danger position-absolute top-0 end-0 mt-3 me-3 rounded-circle p-2" onclick="return confirm('Delete this event?');" style="width:34px; height:34px; display:flex; align-items:center; justify-content:center;" title="Delete Event">
                                    <i class="fas fa-trash"></i>
                                </a>
                                
                                <div class="row align-items-center">
                                    <div class="col-md-3 text-center mb-3 mb-md-0 border-end border-light">
                                        <div class="fw-bold text-primary fs-2"><?php echo date('d', strtotime($ev['event_date'])); ?></div>
                                        <div class="text-uppercase fw-semibold text-secondary"><?php echo date('M Y', strtotime($ev['event_date'])); ?></div>
                                        <div class="badge <?php echo $badge_class; ?> rounded-pill mt-2"><?php echo ucfirst(str_replace('_', ' ', $ev['event_type'])); ?></div>
                                    </div>
                                    <div class="col-md-9 px-md-4">
                                        <h4 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($ev['title']); ?></h4>
                                        <div class="text-muted small mb-2"><i class="fas fa-clock me-1"></i> <?php echo date('h:i A', strtotime($ev['event_date'])); ?></div>
                                        <p class="text-secondary"><?php echo nl2br(htmlspecialchars($ev['description'])); ?></p>
                                        
                                        <div class="d-flex align-items-center mt-3 pt-3 border-top border-light">
                                            <div class="text-muted fw-semibold me-3"><i class="fas fa-users me-1"></i> <?php echo $ev['going_count']; ?> confirmed</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                <?php 
                    endwhile; 
                else: 
                ?>
                    <div class="text-center p-5 bg-white shadow-sm rounded-4 mb-4 border border-light">
                        <i class="fas fa-calendar-times fa-3x text-muted opacity-25 mb-3"></i>
                        <h4 class="text-secondary fw-semibold">No upcoming events</h4>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right Column: Celebrations -->
            <div class="col-lg-4">
                <!-- Birthdays -->
                <div class="card border-0 shadow-sm rounded-4 mb-4" style="background: linear-gradient(135deg, #ffffff 0%, #fff5f5 100%);">
                    <div class="card-body p-4">
                        <h5 class="fw-bold text-danger mb-3"><i class="fas fa-birthday-cake me-2"></i>Birthdays This Month</h5>
                        <?php
                        // Fetch birthdays in current month
                        $bday_sql = "SELECT id, name, profile_image, date_of_birth, DAY(date_of_birth) as day 
                                     FROM users 
                                     WHERE date_of_birth IS NOT NULL 
                                     AND MONTH(date_of_birth) = MONTH(CURRENT_DATE())
                                     ORDER BY DAY(date_of_birth) ASC";
                        $bday_res = $conn->query($bday_sql);
                        
                        if ($bday_res->num_rows > 0):
                            echo '<ul class="list-group list-group-flush bg-transparent">';
                            while ($u = $bday_res->fetch_assoc()):
                                $img = !empty($u['profile_image']) ? '../assets/img/'.htmlspecialchars($u['profile_image']) : 'https://ui-avatars.com/api/?name='.urlencode($u['name']).'&background=ffccd5&color=d63384';
                                $is_today = (date('j') == $u['day']);
                        ?>
                            <li class="list-group-item bg-transparent px-0 d-flex align-items-center border-0 mb-2">
                                <img src="<?php echo $img; ?>" class="rounded-circle me-3 border border-white border-2 shadow-sm" style="width:45px; height:45px; object-fit:cover;" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($u['name']); ?>'">
                                <div>
                                    <div class="fw-bold <?php echo $is_today ? 'text-danger' : 'text-dark'; ?>">
                                        <?php echo htmlspecialchars($u['name']); ?>
                                        <?php if($is_today) echo '<span class="badge bg-danger rounded-pill fw-normal ms-1" style="font-size:0.7rem;">Today! 🎉</span>'; ?>
                                    </div>
                                    <small class="text-muted"><i class="far fa-calendar-alt me-1"></i><?php echo date('F jS', strtotime($u['date_of_birth'])); ?></small>
                                </div>
                            </li>
                        <?php 
                            endwhile;
                            echo '</ul>';
                        else:
                        ?>
                            <div class="text-muted text-center my-3"><i class="fas fa-box-open opacity-25 me-1"></i>No birthdays this month.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Anniversaries -->
                <div class="card border-0 shadow-sm rounded-4" style="background: linear-gradient(135deg, #ffffff 0%, #f0f8ff 100%);">
                    <div class="card-body p-4">
                        <h5 class="fw-bold text-info mb-3"><i class="fas fa-award me-2"></i>Work Anniversaries</h5>
                        <?php
                        $anniv_sql = "SELECT id, name, profile_image, joining_date, (YEAR(CURRENT_DATE()) - YEAR(joining_date)) as years 
                                     FROM users 
                                     WHERE joining_date IS NOT NULL 
                                     AND MONTH(joining_date) = MONTH(CURRENT_DATE())
                                     AND YEAR(joining_date) < YEAR(CURRENT_DATE())
                                     ORDER BY DAY(joining_date) ASC";
                        $anniv_res = $conn->query($anniv_sql);
                        
                        if ($anniv_res->num_rows > 0):
                            echo '<ul class="list-group list-group-flush bg-transparent">';
                            while ($a = $anniv_res->fetch_assoc()):
                                $img = !empty($a['profile_image']) ? '../assets/img/'.htmlspecialchars($a['profile_image']) : 'https://ui-avatars.com/api/?name='.urlencode($a['name']).'&background=cce5ff&color=084298';
                        ?>
                            <li class="list-group-item bg-transparent px-0 d-flex align-items-center border-0 mb-2">
                                <img src="<?php echo $img; ?>" class="rounded-circle me-3 shadow-sm border border-white border-2" style="width:45px; height:45px; object-fit:cover;" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($a['name']); ?>'">
                                <div>
                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($a['name']); ?></div>
                                    <small class="text-info fw-semibold"><?php echo $a['years']; ?> Year<?php echo $a['years']>1?'s':''; ?>!</small>
                                    <small class="text-muted ms-1 text-nowrap"><i class="far fa-calendar me-1"></i><?php echo date('M jS', strtotime($a['joining_date'])); ?></small>
                                </div>
                            </li>
                        <?php 
                            endwhile;
                            echo '</ul>';
                        else:
                        ?>
                            <div class="text-muted text-center my-3"><i class="fas fa-box-open opacity-25 me-1"></i>No anniversaries this month.</div>
                        <?php endif; ?>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>

<!-- Create Event Modal -->
<div class="modal fade" id="createEventModal" tabindex="-1" aria-labelledby="createEventModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <form method="POST">
      <div class="modal-header border-light">
        <h5 class="modal-title fw-bold" id="createEventModalLabel">Create New Event</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <div class="mb-3">
            <label class="form-label fw-bold">Event Title</label>
            <input type="text" name="title" class="form-control" required placeholder="E.g. Annual Team Lunch">
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">Event Category</label>
            <select name="event_type" class="form-select">
                <option value="event">General Event</option>
                <option value="team_building">Team Building</option>
                <option value="social">Social Gathering / After-hours</option>
                <option value="workshop">Learning & Workshop</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">Date & Time</label>
            <input type="datetime-local" name="event_date" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">Details</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Location, agenda, or link..."></textarea>
        </div>
      </div>
      <div class="modal-footer border-light bg-light rounded-bottom-4">
        <button type="button" class="btn btn-outline-secondary rounded-pill fw-semibold px-4" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="create_event" class="btn btn-primary rounded-pill fw-bold px-4">Create Event</button>
      </div>
      </form>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
