<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统安装 - 邮件发送系统</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .install-card {
            max-width: 500px;
            width: 100%;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="install-card">
        <h2 class="text-center mb-4">系统初始化配置</h2>
        <p class="text-muted text-center mb-4">请配置数据库连接信息以完成安装</p>
        
        <div id="alert-box" class="alert alert-danger d-none"></div>

        <form id="installForm">
            <div class="mb-3">
                <label class="form-label">数据库主机 (Host)</label>
                <input type="text" class="form-control" name="host" value="host.docker.internal" required>
                <div class="form-text">Docker环境如果连宿主机请用 host.docker.internal</div>
            </div>
            <div class="mb-3">
                <label class="form-label">端口 (Port)</label>
                <input type="number" class="form-control" name="port" value="3306" required>
            </div>
            <div class="mb-3">
                <label class="form-label">用户名 (User)</label>
                <input type="text" class="form-control" name="user" value="root" required>
            </div>
            <div class="mb-3">
                <label class="form-label">密码 (Password)</label>
                <input type="password" class="form-control" name="password" required>
            </div>
            <div class="mb-3">
                <label class="form-label">数据库名 (Database Name)</label>
                <input type="text" class="form-control" name="db_name" value="email_system" required>
            </div>
            
            <div class="d-grid gap-2 mt-4">
                <button type="submit" class="btn btn-primary" id="btn-submit">开始安装</button>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('installForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('btn-submit');
            const alertBox = document.getElementById('alert-box');
            
            btn.disabled = true;
            btn.innerText = '正在安装...';
            alertBox.classList.add('d-none');
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            data.port = parseInt(data.port); // Convert port to int

            try {
                const response = await fetch('/api/install/setup_db', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (response.ok) {
                    alert('安装成功！正在跳转...');
                    window.location.href = '/index.php';
                } else {
                    alertBox.innerText = '安装失败: ' + (result.detail || '未知错误');
                    alertBox.classList.remove('d-none');
                    btn.disabled = false;
                    btn.innerText = '开始安装';
                }
            } catch (error) {
                alertBox.innerText = '网络错误: ' + error.message;
                alertBox.classList.remove('d-none');
                btn.disabled = false;
                btn.innerText = '开始安装';
            }
        });
        
    </script>
</body>
</html>
