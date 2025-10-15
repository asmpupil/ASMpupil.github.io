<?php
// update.php - 聊天室更新系统（仅管理员访问）

// 包含配置文件
include 'config.php';

// 检查是否管理员
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: chat.php");
    exit();
}

// 更新服务器配置（主备服务器）
$update_servers = [
    "https://asmpupil.github.com",
    "https://ltcupdate.infinityfreeapp.com"
];

$update_path = "/update.txt";
$version_path = "/version.txt";
$local_mark_file = "biaoji.txt";

// 处理跳过版本
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['skip_version'])) {
    $skip_version = trim($_POST['skip_version']);
    if (createLocalMark($skip_version)) {
        header("Location: chat.php");
        exit();
    }
}

// 处理确认更新
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_update'])) {
    $confirm_version = trim($_POST['confirm_version']);
    $update_result = performUpdate($confirm_version);
    $update_results = $update_result['results'];
    $update_description = $update_result['description'];
    
    if (createLocalMark($confirm_version)) {
        $success_message = "更新完成！版本已更新到 $confirm_version";
        if (!empty($update_description)) {
            $success_message .= "<br>更新内容: $update_description";
        }
    } else {
        $error_message = "更新完成但无法创建标记文件";
    }
}

// 获取可用的更新服务器
function getAvailableServer($servers, $path) {
    foreach ($servers as $server) {
        $url = $server . $path;
        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'user_agent' => 'ChatRoomUpdater/1.0'
            ]
        ]);
        
        $content = @file_get_contents($url, false, $context);
        if ($content !== false) {
            return ['server' => $server, 'content' => $content];
        }
    }
    return null;
}

// 获取当前版本
function getCurrentVersion() {
    $mark_file = "biaoji.txt";
    if (file_exists($mark_file)) {
        $content = file_get_contents($mark_file);
        if (preg_match('/版本:\s*([^\s]+)/', $content, $matches)) {
            return $matches[1];
        }
    }
    return "0.0.0"; // 默认版本
}

// 检查是否需要更新
function checkUpdateNeeded($current_version, $latest_version) {
    if ($current_version === "0.0.0") {
        return true; // 未记录版本，需要更新
    }
    
    // 简单的版本号比较
    $current_parts = explode('.', $current_version);
    $latest_parts = explode('.', $latest_version);
    
    for ($i = 0; $i < max(count($current_parts), count($latest_parts)); $i++) {
        $current = isset($current_parts[$i]) ? intval($current_parts[$i]) : 0;
        $latest = isset($latest_parts[$i]) ? intval($latest_parts[$i]) : 0;
        
        if ($latest > $current) {
            return true; // 需要更新
        } elseif ($latest < $current) {
            return false; // 当前版本更高，不需要更新
        }
    }
    
    return false; // 版本相同，不需要更新
}

// 获取最新版本信息
function getLatestVersion($servers) {
    $result = getAvailableServer($servers, $GLOBALS['version_path']);
    if ($result === null) {
        return ['error' => '所有更新服务器都无法访问'];
    }
    
    return [
        'success' => true, 
        'version' => trim($result['content']),
        'server' => $result['server']
    ];
}

// 获取更新信息
function getUpdateInfo($servers) {
    $result = getAvailableServer($servers, $GLOBALS['update_path']);
    if ($result === null) {
        return ['error' => '无法连接到更新服务器'];
    }
    
    return [
        'success' => true, 
        'content' => $result['content'],
        'server' => $result['server']
    ];
}

// 执行更新操作
function performUpdate($latest_version) {
    global $update_servers;
    
    $update_info = getUpdateInfo($update_servers);
    if (isset($update_info['error'])) {
        return ['results' => [], 'description' => '', 'error' => $update_info['error']];
    }
    
    // 解析更新命令
    $commands = array_filter(array_map('trim', explode("\n", $update_info['content'])));
    $results = [];
    $update_description = "";
    
    foreach ($commands as $command) {
        if (preg_match('/^xs:(.+)$/', $command, $matches)) {
            $update_description = trim($matches[1]);
            $results[] = "更新说明: $update_description";
        } elseif (preg_match('/^tg:(.+)$/', $command, $matches)) {
            $filename = trim($matches[1]);
            $result = updateOrAddFile($filename, $update_info['server']);
            $results[] = "文件更新: $filename - " . ($result['success'] ? '成功' : '失败: ' . $result['error']);
        } elseif (preg_match('/^de:(.+)$/', $command, $matches)) {
            $filename = trim($matches[1]);
            $result = deleteFile($filename);
            $results[] = "文件删除: $filename - " . ($result['success'] ? '成功' : '失败: ' . $result['error']);
        } elseif (preg_match('/^dr:(.+)$/', $command, $matches)) {
            $sql_file = trim($matches[1]);
            $result = importDatabase($sql_file, $update_info['server']);
            $results[] = "数据库导入: $sql_file - " . ($result['success'] ? '成功' : '失败: ' . $result['error']);
        } elseif (preg_match('/^mk:(.+)$/', $command, $matches)) {
            $dirname = trim($matches[1]);
            $result = createDirectory($dirname);
            $results[] = "创建目录: $dirname - " . ($result['success'] ? '成功' : '失败: ' . $result['error']);
        }
    }
    
    return ['results' => $results, 'description' => $update_description];
}

// 更新或添加文件
function updateOrAddFile($filename, $server) {
    $remote_file_url = $server . "/files/" . $filename;
    $local_file_path = $filename;
    
    // 获取远程文件内容
    $content = @file_get_contents($remote_file_url);
    if ($content === false) {
        return ['success' => false, 'error' => '无法下载文件'];
    }
    
    // 备份原文件（如果存在）
    if (file_exists($local_file_path)) {
        $backup_path = 'backups/' . $filename . '.' . date('YmdHis');
        if (!is_dir('backups')) {
            mkdir('backups', 0755, true);
        }
        copy($local_file_path, $backup_path);
    }
    
    // 确保目录存在
    $dir = dirname($local_file_path);
    if (!is_dir($dir) && $dir !== '.') {
        mkdir($dir, 0755, true);
    }
    
    // 写入文件
    if (file_put_contents($local_file_path, $content) === false) {
        return ['success' => false, 'error' => '无法写入文件'];
    }
    
    return ['success' => true];
}

// 删除文件
function deleteFile($filename) {
    $local_file_path = $filename;
    
    // 检查文件是否存在
    if (!file_exists($local_file_path)) {
        return ['success' => false, 'error' => '文件不存在'];
    }
    
    // 备份文件
    $backup_path = 'backups/' . $filename . '.' . date('YmdHis');
    if (!is_dir('backups')) {
        mkdir('backups', 0755, true);
    }
    copy($local_file_path, $backup_path);
    
    // 删除文件
    if (unlink($local_file_path) === false) {
        return ['success' => false, 'error' => '无法删除文件'];
    }
    
    return ['success' => true];
}

// 导入数据库
function importDatabase($sql_filename, $server) {
    global $pdo;
    
    $remote_sql_url = $server . "/files/" . $sql_filename;
    
    // 获取SQL文件内容
    $sql_content = @file_get_contents($remote_sql_url);
    if ($sql_content === false) {
        return ['success' => false, 'error' => '无法下载SQL文件'];
    }
    
    try {
        // 分割SQL语句
        $sql_commands = array_filter(array_map('trim', explode(';', $sql_content)));
        
        // 执行每个SQL命令
        foreach ($sql_commands as $sql) {
            if (!empty($sql)) {
                $pdo->exec($sql);
            }
        }
        
        return ['success' => true];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// 创建目录
function createDirectory($dirname) {
    // 清理目录路径
    $dirname = trim($dirname, '/\\');
    
    if (empty($dirname)) {
        return ['success' => false, 'error' => '目录名不能为空'];
    }
    
    // 检查是否已存在
    if (is_dir($dirname)) {
        return ['success' => false, 'error' => '目录已存在'];
    }
    
    // 创建目录（支持递归创建）
    if (mkdir($dirname, 0755, true)) {
        return ['success' => true];
    } else {
        return ['success' => false, 'error' => '无法创建目录'];
    }
}

// 创建本地标记文件
function createLocalMark($version) {
    global $local_mark_file;
    
    $content = "版本: $version\n更新时间: " . date('Y-m-d H:i:s') . "\n";
    
    if (file_put_contents($local_mark_file, $content) !== false) {
        return true;
    }
    return false;
}

// 主逻辑
$current_version = getCurrentVersion();
$latest_version_info = getLatestVersion($update_servers);

if (isset($latest_version_info['error'])) {
    $error_message = $latest_version_info['error'];
} else {
    $latest_version = $latest_version_info['version'];
    $current_server = $latest_version_info['server'];
    
    // 检查是否需要更新
    $needs_update = checkUpdateNeeded($current_version, $latest_version);
    
    if (!$needs_update) {
        // 已经是最新版本，直接跳转
        header("Location: chat.php");
        exit();
    }
}

// 如果已经确认更新，显示结果
if (isset($success_message) || isset($error_message)) {
    // 显示更新结果页面
} elseif (isset($needs_update) && $needs_update) {
    // 显示更新确认页面
    $show_update_confirmation = true;
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统更新 - 高级聊天室</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .update-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 700px;
        }
        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
        .version-info {
            background: #e8f4fd;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
        }
        .server-info {
            background: #fff3cd;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            text-align: center;
            font-size: 14px;
            color: #856404;
        }
        .message {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        .info {
            background: #e8f4fd;
            color: #004085;
            border-left: 4px solid #3498db;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }
        .update-results {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            max-height: 300px;
            overflow-y: auto;
        }
        .update-result-item {
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .update-result-item:last-child {
            border-bottom: none;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
            font-size: 14px;
            margin: 5px;
        }
        .btn-primary {
            background: #3498db;
            color: white;
        }
        .btn-primary:hover {
            background: #2980b9;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-success:hover {
            background: #219a52;
        }
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        .btn-warning:hover {
            background: #e0a800;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .center {
            text-align: center;
        }
        .admin-notice {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
            color: #856404;
        }
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
    </style>
</head>
<body>
    <div class="update-container">
        <h1>🌸 聊天室更新系统</h1>
        
        <div class="admin-notice">
            <strong>管理员专用</strong> - 系统更新页面
        </div>
        
        <div class="version-info">
            <p>当前版本: <strong><?php echo $current_version; ?></strong></p>
            <?php if (isset($latest_version)): ?>
                <p>最新版本: <strong><?php echo $latest_version; ?></strong></p>
            <?php endif; ?>
        </div>

        <?php if (isset($current_server)): ?>
            <div class="server-info">
                当前使用更新服务器: <strong><?php echo $current_server; ?></strong>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="message error">
                <strong>错误:</strong> <?php echo $error_message; ?>
            </div>
            <div class="center">
                <a href="chat.php" class="btn btn-primary">返回聊天室</a>
            </div>
        
        <?php elseif (isset($success_message)): ?>
            <div class="message success">
                <?php echo $success_message; ?>
            </div>
            <?php if (!empty($update_results)): ?>
            <div class="update-results">
                <h3>更新详情:</h3>
                <?php foreach ($update_results as $result): ?>
                    <div class="update-result-item"><?php echo $result; ?></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <div class="center">
                <a href="chat.php" class="btn btn-success">返回聊天室</a>
            </div>
        
        <?php elseif (isset($show_update_confirmation) && $show_update_confirmation): ?>
            <div class="message warning">
                <strong>发现新版本!</strong><br>
                检测到新版本 <?php echo $latest_version; ?>，请选择操作：
            </div>
            
            <div class="action-buttons">
                <form method="POST">
                    <input type="hidden" name="confirm_update" value="<?php echo $latest_version; ?>">
                    <button type="submit" class="btn btn-success">确认更新</button>
                </form>
                
                <form method="POST">
                    <input type="hidden" name="skip_version" value="<?php echo $latest_version; ?>">
                    <button type="submit" class="btn btn-warning">跳过此版本</button>
                </form>
                
                <a href="chat.php" class="btn btn-danger">取消并返回</a>
            </div>
            
            <div style="margin-top: 20px; text-align: center; color: #666;">
                <p><small>选择"跳过此版本"将标记此版本为已更新，直到下一个版本发布</small></p>
            </div>
        
        <?php else: ?>
            <div class="message info">
                正在检查更新...
            </div>
            <script>
                // 自动开始更新检查
                setTimeout(function() {
                    window.location.reload();
                }, 1000);
            </script>
        <?php endif; ?>
        
        <div style="margin-top: 20px; text-align: center; color: #666; font-size: 14px;">
            <p>更新服务器: <?php echo implode(' | ', $update_servers); ?></p>
            <p><a href="chat.php" style="color: #3498db;">返回聊天室</a></p>
        </div>
    </div>
</body>
</html>