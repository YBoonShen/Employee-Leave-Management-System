<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: page-login.php');
    exit;
}
$userName = htmlspecialchars($_SESSION['name'] ?? 'User', ENT_QUOTES, 'UTF-8');
$employeeId = htmlspecialchars($_SESSION['employee_id'] ?? 'EMP001', ENT_QUOTES, 'UTF-8');
$role = htmlspecialchars($_SESSION['role'] ?? 'employee', ENT_QUOTES, 'UTF-8');
?>
<?php include __DIR__ . '/view-dashboard.php'; ?>
<script>
    window.NEXUS_USER = {
        name: "<?php echo $userName; ?>",
        id: "<?php echo $employeeId; ?>",
        role: "<?php echo $role; ?>"
    };
</script>

