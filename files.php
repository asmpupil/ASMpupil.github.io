<?php
// 安全设置：限制访问的目录
$baseDir = __DIR__;
$requestedDir = isset($_GET['dir']) ? $_GET['dir'] : '';

// 防止目录遍历攻击
$requestedPath = realpath($baseDir . '/' . $requestedDir);
if(strpos($requestedPath, $baseDir) !== 0 || $requestedPath === false) {
    $requestedPath = $baseDir;
}

$currentDir = $requestedPath;

// 获取目录内容
$files = [];
$folders = [];

if ($handle = opendir($currentDir)) {
    while (false !== ($entry = readdir($handle))) {
        if ($entry == "." || $entry == "..") continue;
        
        $fullPath = $currentDir . '/' . $entry;
        if (is_dir($fullPath)) {
            $folders[] = [
                'name' => $entry,
                'path' => $fullPath,
                'modified' => date("Y-m-d H:i", filemtime($fullPath))
            ];
        } else {
            $files[] = [
                'name' => $entry,
                'path' => $fullPath,
                'size' => formatSize(filesize($fullPath)),
                'modified' => date("Y-m-d H:i", filemtime($fullPath)),
                'ext' => pathinfo($entry, PATHINFO_EXTENSION)
            ];
        }
    }
    closedir($handle);
}

// 排序：文件夹在前，文件在后
usort($folders, function($a, $b) { return strcmp($a['name'], $b['name']); });
usort($files, function($a, $b) { return strcmp($a['name'], $b['name']); });

// 获取相对路径用于显示面包屑
$relativePath = str_replace($baseDir, '', $currentDir);
if($relativePath === '') $relativePath = '/';

// 文件大小格式化函数
function formatSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        return $bytes . ' bytes';
    } elseif ($bytes == 1) {
        return '1 byte';
    } else {
        return '0 bytes';
    }
}

// 文件类型图标映射
$fileIcons = [
    'folder' => 'fas fa-folder',
    'image' => 'fas fa-file-image',
    'archive' => 'fas fa-file-archive',
    'audio' => 'fas fa-file-audio',
    'video' => 'fas fa-file-video',
    'code' => 'fas fa-file-code',
    'pdf' => 'fas fa-file-pdf',
    'word' => 'fas fa-file-word',
    'excel' => 'fas fa-file-excel',
    'ppt' => 'fas fa-file-powerpoint',
    'text' => 'fas fa-file-alt',
    'default' => 'fas fa-file'
];

function getFileIcon($ext, $isDir = false) {
    global $fileIcons;
    if ($isDir) return $fileIcons['folder'];
    
    $imageTypes = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'];
    $archiveTypes = ['zip', 'rar', '7z', 'tar', 'gz', 'bz2'];
    $codeTypes = ['php', 'js', 'html', 'css', 'py', 'java', 'cpp', 'c', 'h', 'xml', 'json'];
    
    if (in_array($ext, $imageTypes)) return $fileIcons['image'];
    if (in_array($ext, $archiveTypes)) return $fileIcons['archive'];
    if (in_array($ext, $codeTypes)) return $fileIcons['code'];
    
    switch (strtolower($ext)) {
        case 'pdf': return $fileIcons['pdf'];
        case 'doc': case 'docx': return $fileIcons['word'];
        case 'xls': case 'xlsx': return $fileIcons['excel'];
        case 'ppt': case 'pptx': return $fileIcons['ppt'];
        case 'txt': case 'log': return $fileIcons['text'];
        case 'mp3': case 'wav': case 'ogg': return $fileIcons['audio'];
        case 'mp4': case 'mov': case 'avi': case 'mkv': return $fileIcons['video'];
        default: return $fileIcons['default'];
    }
}

// 生成面包屑导航
function generateBreadcrumbs($base, $path) {
    $crumbs = [];
    $pathParts = explode('/', trim($path, '/'));
    $currentPath = '';
    
    $crumbs[] = [
        'name' => '根目录',
        'path' => ''
    ];
    
    foreach ($pathParts as $part) {
        if (!empty($part)) {
            $currentPath .= '/' . $part;
            $crumbs[] = [
                'name' => $part,
                'path' => $currentPath
            ];
        }
    }
    
    return $crumbs;
}

$breadcrumbs = generateBreadcrumbs($baseDir, $relativePath);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>爱数码资源库</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Microsoft YaHei', sans-serif;
        }
        
        body {
            background-color: #f9f9f9;
            color: #333;
            line-height: 1.6;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: 1px solid #eee;
        }
        
        .header h1 {
            font-size: 2.8rem;
            color: #000;
            letter-spacing: 2px;
            font-weight: 700;
        }
        
        .breadcrumb {
            background-color: #e9ecef;
            padding: 15px 20px;
            border-radius: 5px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
            font-size: 1rem;
        }
        
        .breadcrumb a {
            color: #0d6efd;
            text-decoration: none;
            padding: 3px 8px;
            border-radius: 3px;
            margin-right: 5px;
        }
        
        .breadcrumb a:hover {
            background-color: #dae0e5;
        }
        
        .breadcrumb .sep {
            margin: 0 5px;
            color: #6c757d;
        }
        
        .path-info {
            margin-left: auto;
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 5px;
            width: 100%;
        }
        
        .file-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .file-item {
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0,0,0,0.06);
            transition: all 0.3s ease;
            border: 1px solid #eaeaea;
            display: flex;
            flex-direction: column;
            position: relative;
        }
        
        .file-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
            border-color: #4b6cb7;
        }
        
        .file-icon {
            height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(to bottom, #f9f9f9, #eaeaea);
        }
        
        .folder .file-icon {
            background: linear-gradient(to bottom, #e1f0ff, #b5d5ff);
        }
        
        .file-icon i {
            font-size: 3rem;
            color: #4b6cb7;
        }
        
        .folder .file-icon i {
            color: #185abc;
        }
        
        .file-info {
            padding: 15px;
            border-top: 1px solid #eee;
        }
        
        .file-name {
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
            font-size: 1rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .file-details {
            display: flex;
            flex-direction: column;
            font-size: 0.85rem;
            color: #666;
        }
        
        .file-size {
            font-family: monospace;
            margin-bottom: 3px;
        }
        
        .file-date {
            color: #666;
            font-size: 0.8rem;
        }
        
        .action-btn {
            display: block;
            width: 100%;
            padding: 8px 10px;
            color: #fff;
            text-align: center;
            text-decoration: none;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .download-btn {
            background: #4b6cb7;
        }
        
        .open-btn {
            background: #28a745;
        }
        
        .action-btn:hover {
            background: #3a5795;
        }
        
        .folder .action-btn:hover {
            background: #218838;
        }
        
        .footer {
            text-align: center;
            padding: 20px;
            margin-top: 30px;
            color: #666;
            font-size: 0.9rem;
            border-top: 1px solid #ddd;
        }
        
        .no-files {
            grid-column: 1 / -1;
            text-align: center;
            padding: 40px;
            background: #f8f9fa;
            border-radius: 8px;
            color: #6c757d;
            font-size: 1.1rem;
        }
        
        .no-files i {
            font-size: 3rem;
            color: #ced4da;
            margin-bottom: 15px;
            display: block;
        }
        
        .system-info {
            margin-top: 10px;
            font-size: 0.8rem;
            color: #888;
        }
        
        @media (max-width: 768px) {
            .file-grid {
                grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
                gap: 15px;
            }
            
            .header h1 {
                font-size: 2.2rem;
            }
            
            .file-icon {
                height: 100px;
            }
            
            .file-icon i {
                font-size: 2.5rem;
            }
        }
        
        @media (max-width: 480px) {
            .file-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 12px;
            }
            
            .header h1 {
                font-size: 1.8rem;
            }
            
            .file-icon {
                height: 90px;
            }
            
            .file-icon i {
                font-size: 2.2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>爱数码的小学生的资源库</h1>
            <div class="system-info">
                服务器时间: <?php echo date('Y-m-d H:i:s'); ?>
            </div>
        </header>
        
        <div class="breadcrumb">
            <?php
            $breadCount = count($breadcrumbs);
            foreach ($breadcrumbs as $index => $crumb):
                $isLast = ($index === $breadCount - 1);
                $pathParam = $crumb['path'] ? "?dir=" . urlencode($crumb['path']) : '';
            ?>
                <a href="<?php echo $pathParam; ?>">
                    <?php echo htmlspecialchars($crumb['name']); ?>
                </a>
                <?php if (!$isLast): ?>
                    <span class="sep">/</span>
                <?php endif; ?>
            <?php endforeach; ?>
            
            <div class="path-info">
                当前目录: <?php echo htmlspecialchars($relativePath); ?>
            </div>
        </div>
        
        <div class="file-grid">
            <?php if (empty($folders) && empty($files)): ?>
                <div class="no-files">
                    <i class="fas fa-folder-open"></i>
                    当前目录为空
                </div>
            <?php else: ?>
                <?php foreach ($folders as $folder): ?>
                    <div class="file-item folder">
                        <div class="file-icon">
                            <i class="<?php echo getFileIcon('', true); ?>"></i>
                        </div>
                        <div class="file-info">
                            <div class="file-name"><?php echo htmlspecialchars($folder['name']); ?></div>
                            <div class="file-details">
                                <span class="file-date">修改时间: <?php echo $folder['modified']; ?></span>
                            </div>
                        </div>
                        <a href="?dir=<?php echo urlencode(str_replace($baseDir, '', $folder['path'])); ?>" class="action-btn open-btn">
                            打开文件夹
                        </a>
                    </div>
                <?php endforeach; ?>
                
                <?php foreach ($files as $file): ?>
                    <div class="file-item">
                        <div class="file-icon">
                            <i class="<?php echo getFileIcon($file['ext']); ?>"></i>
                        </div>
                        <div class="file-info">
                            <div class="file-name"><?php echo htmlspecialchars($file['name']); ?></div>
                            <div class="file-details">
                                <span class="file-size">大小: <?php echo $file['size']; ?></span>
                                <span class="file-date">修改: <?php echo $file['modified']; ?></span>
                            </div>
                        </div>
                        <a href="<?php echo '?dir=' . urlencode($relativePath) . '&download=' . urlencode($file['name']); ?>" class="action-btn download-btn">
                            下载文件
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="footer">
            <p>© 2025 爱数码的小学生 | 文件系统浏览器 | <?php echo '服务器: ' . $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
        </div>
    </div>
</body>
</html>
<?php
// 文件下载处理
if (isset($_GET['download']) && isset($_GET['dir'])) {
    $file = $_GET['download'];
    $dir = $_GET['dir'];
    
    // 安全验证
    $filePath = realpath($baseDir . '/' . $dir . '/' . $file);
    if ($filePath && strpos($filePath, $baseDir) === 0 && is_file($filePath)) {
        // 设置下载头
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        flush();
        readfile($filePath);
        exit;
    } else {
        // 无效文件
        header("HTTP/1.0 404 Not Found");
        echo "文件未找到";
    }
}
?>
