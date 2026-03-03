<?php
// user/hub.php - Social Hub for Employees
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'employee') {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success = '';

// Handle RSVP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rsvp_event'])) {
    $event_id = (int)$_POST['event_id'];
    $status = $conn->real_escape_string($_POST['rsvp_status']);
    
    // Insert or update on duplicate
    $stmt = $conn->prepare("INSERT INTO event_rsvps (event_id, user_id, status) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE status = ?");
    $stmt->bind_param('iiss', $event_id, $user_id, $status, $status);
    $stmt->execute();
    $stmt->close();
    
    $success = "RSVP updated!";
}

include '../includes/header.php';
?>

<div class="page-container">
    <?php include '../includes/sidebar_user.php'; ?>
    <div class="main-content">
        <div class="mb-4 mt-2">
            <h2 class="fw-bold mb-1"><i class="fas fa-calendar-alt text-primary me-2"></i>Company Social Hub</h2>
            <p class="text-muted fs-5">Events, celebrations, and team gatherings.</p>
        </div>

        <?php if($success): ?>
            <div class="alert alert-success border-0 shadow-sm rounded-4"><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="row">
            <!-- Left Column: Events -->
            <div class="col-lg-8">
                <h4 class="fw-bold mb-3">Upcoming Events</h4>
                
                <?php
                $events_sql = "SELECT e.*, 
                               (SELECT COUNT(*) FROM event_rsvps r WHERE r.event_id=e.id AND r.status='going') as going_count,
                               (SELECT status FROM event_rsvps r2 WHERE r2.event_id=e.id AND r2.user_id=$user_id) as my_status 
                               FROM events e WHERE DATE(e.event_date) >= CURRENT_DATE() ORDER BY e.event_date ASC";
                $events_res = $conn->query($events_sql);
                
                if ($events_res->num_rows > 0):
                    while ($ev = $events_res->fetch_assoc()):
                        // Determine badge color
                        $badge_class = 'bg-primary';
                        if ($ev['event_type'] == 'team_building') $badge_class = 'bg-success';
                        if ($ev['event_type'] == 'social') $badge_class = 'bg-info';
                        if ($ev['event_type'] == 'workshop') $badge_class = 'bg-warning text-dark';
                ?>
                        <div class="card border-0 shadow-sm rounded-4 mb-3 border-start border-4 pt-2" style="border-left-color: var(--bs-primary) !important;">
                            <div class="card-body px-4 pb-4">
                                <div class="row align-items-center">
                                    <div class="col-md-3 text-center mb-4 mb-md-0 border-end border-light">
                                        <div class="fw-bold text-primary fs-2"><?php echo date('d', strtotime($ev['event_date'])); ?></div>
                                        <div class="text-uppercase fw-semibold text-secondary"><?php echo date('M Y', strtotime($ev['event_date'])); ?></div>
                                        <div class="badge <?php echo $badge_class; ?> rounded-pill mt-2 px-3 py-2 shadow-sm"><?php echo ucfirst(str_replace('_', ' ', $ev['event_type'])); ?></div>
                                    </div>
                                    <div class="col-md-9 px-md-4">
                                        <h4 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($ev['title']); ?></h4>
                                        <div class="text-muted small mb-2"><i class="fas fa-clock me-1 text-primary"></i> <?php echo date('l, h:i A', strtotime($ev['event_date'])); ?></div>
                                        <p class="text-secondary" style="line-height:1.6;"><?php echo nl2br(htmlspecialchars($ev['description'])); ?></p>
                                        
                                        <div class="d-flex align-items-center justify-content-between mt-3 pt-3 border-top border-light">
                                            <div class="text-muted fw-semibold small">
                                                <i class="fas fa-users text-primary me-1"></i> <?php echo (int)$ev['going_count']; ?> colleague(s) attending
                                            </div>
                                            
                                            <form method="POST" class="d-flex gap-2 m-0 mt-3 pt-3 border-top border-light">
                                                <input type="hidden" name="event_id" value="<?php echo $ev['id']; ?>">
                                                <?php $my_stat = $ev['my_status'] ?? ''; ?>
                                                <button type="submit" name="rsvp_status" value="going" class="btn btn-sm rounded-pill fw-bold px-3 <?php echo ($my_stat=='going') ? 'btn-success':'btn-outline-success'; ?>"><i class="fas fa-check me-1"></i> Going</button>
                                                <button type="submit" name="rsvp_status" value="maybe" class="btn btn-sm rounded-pill fw-bold px-3 <?php echo ($my_stat=='maybe') ? 'btn-warning text-dark':'btn-outline-warning text-dark'; ?>">? Maybe</button>
                                                <button type="submit" name="rsvp_status" value="declined" class="btn btn-sm rounded-pill fw-bold px-3 <?php echo ($my_stat=='declined') ? 'btn-secondary':'btn-outline-secondary'; ?>">Can't Go</button>
                                                <input type="hidden" name="rsvp_event" value="1">
                                            </form>
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
                        <h4 class="text-secondary fw-semibold">No upcoming events right now</h4>
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
                                $is_me = ($u['id'] == $user_id);
                        ?>
                            <li class="list-group-item bg-transparent px-0 d-flex align-items-center border-0 mb-2">
                                <img src="<?php echo $img; ?>" class="rounded-circle me-3 border border-white border-2 shadow-sm" style="width:45px; height:45px; object-fit:cover;" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($u['name']); ?>'">
                                <div>
                                    <div class="fw-bold <?php echo $is_today ? 'text-danger' : 'text-dark'; ?>">
                                        <?php echo htmlspecialchars($u['name']); ?> <?php if($is_me) echo '<small class="text-muted fw-normal">(You)</small>'; ?>
                                        <?php if($is_today): ?><span class="badge bg-danger rounded-pill fw-normal ms-1 shadow-sm" style="font-size:0.7rem;">Today! 🎉</span><?php endif; ?>
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
                        
                        <?php 
                        // Check if current user has DOB set, if not, show reminder
                        $my_dob_check = $conn->query("SELECT date_of_birth FROM users WHERE id=$user_id AND date_of_birth IS NULL");
                        if($my_dob_check->num_rows > 0): 
                        ?>
                        <div class="alert alert-light border border-light mt-3 py-2 px-3 small rounded-3 mb-0">
                            Missing out? <a href="edit_user.php" class="text-danger fw-bold text-decoration-none">Add your birthday!</a>
                        </div>
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
                                $is_me = ($a['id'] == $user_id);
                        ?>
                            <li class="list-group-item bg-transparent px-0 d-flex align-items-center border-0 mb-2">
                                <img src="<?php echo $img; ?>" class="rounded-circle me-3 shadow-sm border border-white border-2" style="width:45px; height:45px; object-fit:cover;" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($a['name']); ?>'">
                                <div>
                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($a['name']); ?> <?php if($is_me) echo '<small class="text-muted fw-normal">(You)</small>'; ?></div>
                                    <div class="d-flex align-items-center">
                                        <small class="text-info fw-bold bg-white px-2 rounded-1 border border-info border-opacity-25 me-2"><?php echo $a['years']; ?> Year<?php echo $a['years']>1?'s':''; ?>!</small>
                                        <small class="text-muted text-nowrap mt-1"><i class="far fa-calendar me-1"></i><?php echo date('M jS', strtotime($a['joining_date'])); ?></small>
                                    </div>
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

<?php include '../includes/footer.php'; ?>
