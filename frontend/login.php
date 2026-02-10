<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录 - 邮件发送系统</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f9;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-box {
            width: 360px;
            background: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h3 class="text-center mb-4">邮件发送系统</h3>
        <p class="login-box-msg text-center text-muted">请登录以开始会话</p>

        <form id="login-form">
            <div class="mb-3">
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="用户名" id="username" required>
                </div>
            </div>
            <div class="mb-3">
                <div class="input-group">
                    <input type="password" class="form-control" placeholder="密码" id="password" required>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary w-100">登录</button>
                </div>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('login-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            
            try {
                const response = await fetch('/api/auth/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ username, password })
                });
                
                if (!response.ok) {
                    const data = await response.json();
                    throw new Error(data.detail || '登录失败');
                }
                
                const data = await response.json();
                localStorage.setItem('token', data.token);
                localStorage.setItem('username', data.username);
                localStorage.setItem('expires_at', data.expires_at);
                
                window.location.href = '/index.php';
                
            } catch (error) {
                alert(error.message);
            }
        });
    </script>
</body>
</html>
