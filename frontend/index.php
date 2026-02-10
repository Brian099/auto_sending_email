<?php
$active_page = 'dashboard';
include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">仪表盘</h1>
</div>

<div class="row">
    <div class="col-lg-3 col-6">
        <!-- small box -->
        <div class="card text-white bg-success mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title display-4 fw-bold" id="recipients-count">-</h3>
                        <p class="card-text">总收件人</p>
                    </div>
                    <i class="bi bi-people" style="font-size: 3rem; opacity: 0.5;"></i>
                </div>
            </div>
            <a href="recipients.php" class="card-footer text-white text-center text-decoration-none">
                更多信息 <i class="bi bi-arrow-right-circle"></i>
            </a>
        </div>
    </div>
    <!-- ./col -->
    <div class="col-lg-3 col-6">
        <!-- small box -->
        <div class="card text-white bg-primary mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title display-4 fw-bold" id="tasks-count">-</h3>
                        <p class="card-text">总发送任务</p>
                    </div>
                    <i class="bi bi-send" style="font-size: 3rem; opacity: 0.5;"></i>
                </div>
            </div>
            <a href="tasks.php" class="card-footer text-white text-center text-decoration-none">
                更多信息 <i class="bi bi-arrow-right-circle"></i>
            </a>
        </div>
    </div>
    <!-- ./col -->
    <div class="col-lg-3 col-6">
        <!-- small box -->
        <div class="card text-white bg-info mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title display-4 fw-bold" id="senders-count">-</h3>
                        <p class="card-text">总发件箱</p>
                    </div>
                    <i class="bi bi-envelope-at" style="font-size: 3rem; opacity: 0.5;"></i>
                </div>
            </div>
            <a href="senders.php" class="card-footer text-white text-center text-decoration-none">
                更多信息 <i class="bi bi-arrow-right-circle"></i>
            </a>
        </div>
    </div>
    <!-- ./col -->
    <div class="col-lg-3 col-6">
        <!-- small box -->
        <div class="card text-white bg-warning mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title display-4 fw-bold" id="templates-count">-</h3>
                        <p class="card-text">总模板</p>
                    </div>
                    <i class="bi bi-file-earmark-richtext" style="font-size: 3rem; opacity: 0.5;"></i>
                </div>
            </div>
            <a href="templates.php" class="card-footer text-white text-center text-decoration-none">
                更多信息 <i class="bi bi-arrow-right-circle"></i>
            </a>
        </div>
    </div>
    <!-- ./col -->
</div>

<script>
    document.addEventListener('DOMContentLoaded', async () => {
        try {
            const stats = await apiCall('/dashboard/stats');
            
            document.getElementById('recipients-count').innerText = stats.total_recipients;
            document.getElementById('tasks-count').innerText = stats.total_tasks;
            document.getElementById('senders-count').innerText = stats.total_senders;
            document.getElementById('templates-count').innerText = stats.total_templates;
            
        } catch (e) {
            console.error("Failed to load dashboard stats", e);
        }
    });
</script>

<?php include 'includes/footer.php'; ?>
