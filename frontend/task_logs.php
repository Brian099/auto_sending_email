<?php
$active_page = 'tasks';
include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">任务日志 <small class="text-muted" id="task-id-display"></small></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="tasks.php" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> 返回任务列表
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="table-responsive">
            <table class="table table-striped table-hover table-sm">
                <thead>
                    <tr>
                        <th>时间</th>
                        <th>收件人邮箱</th>
                        <th>发件人邮箱</th>
                        <th>模版名称</th>
                        <th>结果</th>
                        <th>信息</th>
                    </tr>
                </thead>
                <tbody id="logs-table-body">
                    <tr><td colspan="6" class="text-center text-muted">正在加载日志...</td></tr>
                </tbody>
            </table>
        </div>
        <div class="d-grid gap-2 mt-3">
             <button class="btn btn-outline-primary" id="load-more-btn" onclick="loadMoreLogs()">加载更多</button>
        </div>
    </div>
</div>

<script>
    const urlParams = new URLSearchParams(window.location.search);
    const taskId = urlParams.get('task_id');
    let currentOffset = 0;
    const limit = 50;
    let autoRefreshInterval = null;

    if (!taskId) {
        alert('未指定任务ID');
        window.location.href = 'tasks.php';
    }

    document.getElementById('task-id-display').innerText = `(ID: ${taskId})`;

    async function loadLogs(append = false) {
        if (!append) {
            currentOffset = 0; // Reset if refreshing/initial load
        }
        
        try {
            // If appending, we fetch older logs (offset increases)
            // But wait, usually logs are sorted by time desc. 
            // So offset 0 gives newest.
            // If we want "real-time" monitoring, we usually just want the newest logs.
            // If we want "history", we page down.
            
            // Let's keep it simple: Auto-refresh only refreshes the top (newest).
            // "Load More" appends older logs at bottom.
            
            const endpoint = `/tasks/${taskId}/logs?limit=${limit}&offset=${currentOffset}`;
            const data = await apiCall(endpoint);
            const tbody = document.getElementById('logs-table-body');
            
            if (!append) {
                tbody.innerHTML = '';
            }
            
            if (data.length === 0) {
                if (!append) tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">暂无日志</td></tr>';
                // Disable load more if no data returned
                if (append) {
                    document.getElementById('load-more-btn').disabled = true;
                    document.getElementById('load-more-btn').innerText = '没有更多日志了';
                }
                return;
            }

            data.forEach(log => {
                const color = log.status === 'success' ? 'text-success' : 'text-danger';
                tbody.innerHTML += `
                    <tr>
                        <td>${new Date(log.sent_at).toLocaleString()}</td>
                        <td>${log.recipient_email || log.recipient_id}</td>
                        <td>${log.sender_email || log.sender_id}</td>
                        <td>${log.template_subject || log.template_id}</td>
                        <td class="${color}">${log.status}</td>
                        <td class="small" title="${log.error_message || ''}">${log.error_message || '-'}</td>
                    </tr>
                `;
            });
            
            if (data.length < limit) {
                 document.getElementById('load-more-btn').disabled = true;
                 document.getElementById('load-more-btn').innerText = '没有更多日志了';
            } else {
                 document.getElementById('load-more-btn').disabled = false;
                 document.getElementById('load-more-btn').innerText = '加载更多';
            }
            
            // Update offset for next "load more"
            currentOffset += data.length;

        } catch (e) {
            console.error(e);
            if (!append) document.getElementById('logs-table-body').innerHTML = '<tr><td colspan="6" class="text-center text-danger">加载失败</td></tr>';
        }
    }
    
    async function refreshNewest() {
        // Just reload the first page for now to keep it simple and see latest status
        // A better implementation would be to fetch *only* logs newer than the top one.
        // For now, let's just reset and reload.
        currentOffset = 0;
        await loadLogs(false);
    }

    function loadMoreLogs() {
        loadLogs(true);
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadLogs(false);
        // Auto refresh every 3s
        autoRefreshInterval = setInterval(refreshNewest, 3000);
    });
    
    // Stop auto-refresh if user scrolls down or interacts? 
    // Maybe simpler: Stop auto-refresh if user clicks "Load More" to avoid jumping content
    document.getElementById('load-more-btn').addEventListener('click', () => {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
            autoRefreshInterval = null;
        }
    });

</script>

<?php include 'includes/footer.php'; ?>