#!/bin/bash
set -e

# 尝试修复权限问题
echo "Fixing permissions..."

# 确保 /var/www/html 目录存在
mkdir -p /var/www/html

# 尝试更改所有者为 www-data (Apache 默认用户)
# 在 Windows 挂载中这可能会失败，所以加上 || true
chown -R www-data:www-data /var/www/html || true

# 尝试赋予所有权限
chmod -R 777 /var/www/html || true

# 确保上传目录权限
mkdir -p /app/uploads
chown -R www-data:www-data /app/uploads || true
chmod -R 777 /app/uploads || true

# 启动 Supervisor
echo "Starting Supervisor..."
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
