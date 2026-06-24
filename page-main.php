<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: page-login.php');
    exit;
}
function se($key, $default = '') {
    return htmlspecialchars($_SESSION[$key] ?? $default, ENT_QUOTES, 'UTF-8');
}
?>
<?php include __DIR__ . '/view-dashboard.php'; ?>
<script>
    window.NEXUS_USER = {
        name:            "<?php echo se('name', 'User'); ?>",
        id:              "<?php echo se('employee_id', 'EMP001'); ?>",
        role:            "<?php echo se('role', 'employee'); ?>",
        email:           "<?php echo se('email'); ?>",
        department:      "<?php echo se('department'); ?>",
        job_title:       "<?php echo se('job_title'); ?>",
        phone:           "<?php echo se('phone'); ?>",
        location:        "<?php echo se('location'); ?>",
        employment_type: "<?php echo se('employment_type', 'Permanent'); ?>",
        join_date:       "<?php echo se('join_date'); ?>"
    };
</script>

