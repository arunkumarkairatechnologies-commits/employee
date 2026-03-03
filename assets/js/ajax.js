// assets/js/ajax.js
// AJAX functions for EMS

$(document).ready(function() {
    // Mark task as completed
    $('.complete-task-btn').on('click', function() {
        var taskId = $(this).data('id');
        $.ajax({
            url: '../user/my_tasks.php',
            type: 'POST',
            data: { complete_task: 1, task_id: taskId },
            success: function(response) {
                location.reload();
            }
        });
    });

    // Mark notification as read
    $('.mark-read-btn').on('click', function() {
        var notifId = $(this).data('id');
        $.ajax({
            url: '../user/dashboard.php',
            type: 'POST',
            data: { mark_read: 1, notif_id: notifId },
            success: function(response) {
                location.reload();
            }
        });
    });

    // Attendance check-in/out
    $('#checkin-btn, #checkout-btn').on('click', function() {
        var action = $(this).attr('id') === 'checkin-btn' ? 'checkin' : 'checkout';
        $.ajax({
            url: '../user/checkin.php',
            type: 'POST',
            data: { action: action },
            success: function(response) {
                location.reload();
            }
        });
    });
});
