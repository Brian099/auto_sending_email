<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>邮件发送系统</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <script>
        // Check installation status
        if (window.location.pathname !== '/install.php') {
            fetch('/api/install/status')
                .then(res => {
                    if (!res.ok) throw new Error('Status check failed');
                    return res.json();
                })
                .then(data => {
                    if (!data.installed) {
                        window.location.href = '/install.php';
                    }
                })
                .catch(e => {
                    console.error("Install check failed", e);
                });
        }
    </script>
    
    <style>
        :root {
            --sidebar-width: 250px;
            --header-height: 57px;
            --primary-color: #0d6efd;
            --sidebar-bg: #343a40;
            --sidebar-color: #c2c7d0;
            --sidebar-hover: #ffffff;
            --sidebar-active-bg: #0d6efd;
            --sidebar-active-color: #ffffff;
        }

        body {
            background-color: #f4f6f9;
            font-family: "Source Sans Pro", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* --- Layout --- */
        .wrapper {
            display: flex;
            width: 100%;
            align-items: stretch;
        }

        .main-header {
            position: fixed;
            top: 0;
            right: 0;
            left: var(--sidebar-width);
            height: var(--header-height);
            z-index: 1030;
            background-color: #fff;
            border-bottom: 1px solid #dee2e6;
            transition: left 0.3s ease-in-out;
        }

        .main-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: var(--sidebar-width);
            height: 100vh;
            z-index: 1038;
            background-color: var(--sidebar-bg);
            color: var(--sidebar-color);
            transition: margin-left 0.3s ease-in-out, width 0.3s ease-in-out;
            box-shadow: 0 14px 28px rgba(0,0,0,.25), 0 10px 10px rgba(0,0,0,.22);
        }

        .content-wrapper {
            margin-left: var(--sidebar-width);
            margin-top: var(--header-height);
            padding: 1.5rem;
            /* width: 100%; Removed to prevent overflow */
            min-height: calc(100vh - var(--header-height));
            transition: margin-left 0.3s ease-in-out;
            background-color: #f4f6f9;
        }

        /* --- Sidebar Styling --- */
        .brand-link {
            display: block;
            height: var(--header-height);
            line-height: var(--header-height);
            font-size: 1.25rem;
            text-align: center;
            background-color: rgba(0,0,0,.1);
            color: #fff;
            text-decoration: none;
            border-bottom: 1px solid #4b545c;
            font-weight: 300;
        }
        .brand-link:hover { color: #fff; }

        .sidebar-menu {
            padding: 0.5rem;
            list-style: none;
            margin: 0;
        }

        .nav-item {
            margin-bottom: 0.2rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.5rem 1rem;
            color: var(--sidebar-color);
            border-radius: 0.25rem;
            text-decoration: none;
        }

        .nav-link:hover {
            color: var(--sidebar-hover);
            background-color: rgba(255,255,255,.1);
        }

        .nav-link.active {
            background-color: var(--sidebar-active-bg);
            color: var(--sidebar-active-color);
            box-shadow: 0 1px 3px rgba(0,0,0,.12), 0 1px 2px rgba(0,0,0,.24);
        }

        .nav-link i {
            margin-right: 0.8rem;
            font-size: 1.1rem;
            width: 1.5rem;
            text-align: center;
            line-height: 1; /* Fix vertical alignment */
        }
        
        .nav-link p {
            margin: 0; /* Remove paragraph margin */
        }

        /* --- Card Styling (AdminLTE style) --- */
        .card {
            box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
            margin-bottom: 1rem;
            border-radius: 0.25rem;
            border: 0;
            background: #fff;
        }
        .card-header {
            background-color: transparent;
            border-bottom: 1px solid rgba(0,0,0,.125);
            padding: 0.75rem 1.25rem;
            position: relative;
            border-top-left-radius: 0.25rem;
            border-top-right-radius: 0.25rem;
        }
        .card-primary.card-outline { border-top: 3px solid #007bff; }
        .card-success.card-outline { border-top: 3px solid #28a745; }
        .card-warning.card-outline { border-top: 3px solid #ffc107; }
        .card-danger.card-outline { border-top: 3px solid #dc3545; }

        /* --- Collapsed State --- */
        body.sidebar-collapse .main-sidebar {
            margin-left: calc(-1 * var(--sidebar-width));
        }
        body.sidebar-collapse .content-wrapper,
        body.sidebar-collapse .main-header,
        body.sidebar-collapse .main-footer {
            margin-left: 0;
        }

        /* --- Responsive --- */
        @media (max-width: 768px) {
            .main-sidebar { margin-left: calc(-1 * var(--sidebar-width)); }
            .content-wrapper, .main-header, .main-footer { margin-left: 0; }
            
            body.sidebar-open .main-sidebar { margin-left: 0; }
            body.sidebar-open .content-wrapper, 
            body.sidebar-open .main-header,
            body.sidebar-open .main-footer { margin-left: 0; } /* Overlay or push? Overlay is better for mobile */
            
            /* Add overlay when sidebar open on mobile */
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0; left: 0; bottom: 0; right: 0;
                background: rgba(0,0,0,0.5);
                z-index: 1037;
            }
            body.sidebar-open .sidebar-overlay { display: block; }
        }
    </style>
</head>
<body>

    <!-- Overlay for mobile -->
    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" href="#" role="button" onclick="toggleSidebar()"><i class="bi bi-list"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="/" class="nav-link">首页</a>
            </li>
        </ul>
        <ul class="navbar-nav ms-auto">
            <!-- Right navbar links -->
            <li class="nav-item">
                <a class="nav-link" href="#" role="button">
                    <i class="bi bi-person-circle"></i> Admin
                </a>
            </li>
        </ul>
    </nav>

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar">
        <!-- Brand Logo -->
        <a href="/" class="brand-link">
            <span class="brand-text font-weight-light">邮件发送系统</span>
        </a>

        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column sidebar-menu" role="menu">
                    
                    <li class="nav-item">
                        <a href="index.php" class="nav-link <?php echo ($active_page == 'dashboard') ? 'active' : ''; ?>">
                            <i class="bi bi-speedometer2"></i>
                            <p>仪表盘</p>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="tasks.php" class="nav-link <?php echo ($active_page == 'tasks') ? 'active' : ''; ?>">
                            <i class="bi bi-send"></i>
                            <p>发送任务</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="recipients.php" class="nav-link <?php echo ($active_page == 'recipients') ? 'active' : ''; ?>">
                            <i class="bi bi-people"></i>
                            <p>收件人管理</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="groups.php" class="nav-link <?php echo ($active_page == 'groups') ? 'active' : ''; ?>">
                            <i class="bi bi-collection"></i>
                            <p>收件人分组</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="senders.php" class="nav-link <?php echo ($active_page == 'senders') ? 'active' : ''; ?>">
                            <i class="bi bi-envelope-at"></i>
                            <p>发件箱管理</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="templates.php" class="nav-link <?php echo ($active_page == 'templates') ? 'active' : ''; ?>">
                            <i class="bi bi-file-earmark-richtext"></i>
                            <p>邮件模板</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="settings.php" class="nav-link <?php echo ($active_page == 'settings') ? 'active' : ''; ?>">
                            <i class="bi bi-gear"></i>
                            <p>系统设置</p>
                        </a>
                    </li>

                </ul>
            </nav>
        </div>
    </aside>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
