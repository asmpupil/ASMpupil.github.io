<?php
// 数据库连接信息
$servername = "localhost";
$username = "ser212743304777"; // 请根据您的数据库配置修改
$password = "Waxxm1031"; // 请根据您的数据库配置修改
$dbname = "ser212743304777";

// 创建数据库连接
$conn = new mysqli($servername, $username, $password, $dbname);

// 检查连接
if ($conn->connect_error) {
    die("数据库连接失败: " . $conn->connect_error);
}

// 处理表单提交
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $student_name = htmlspecialchars($_POST['student_name']);
    $teacher_subject = $_POST['teacher_subject'];
    $message = htmlspecialchars($_POST['message']);
    
    // 插入数据到数据库
    $sql = "INSERT INTO messages (student_name, teacher_subject, message) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $student_name, $teacher_subject, $message);
    
    if ($stmt->execute()) {
     $show_success_message = true;
    } else {
        $error_msg = "提交失败，请重试: " . $conn->error;
    }
    
    $stmt->close();
}

// 从数据库获取教师信息
$teacher_messages = array(
    '语文' => array(),
    '数学' => array(),
    '英语' => array()
);

$sql = "SELECT teacher_subject, student_name, message FROM messages ORDER BY submit_time DESC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $teacher_messages[$row['teacher_subject']][] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<script>
// 显示成功消息并延迟刷新
function showSuccessAndRefresh() {
    // 创建成功提示元素
    const successAlert = document.createElement('div');
    successAlert.className = 'alert alert-success';
    successAlert.innerHTML = '信息提交成功！页面将在1秒后刷新...';
    successAlert.style.cssText = 'position: fixed; top: 20px; left: 50%; transform: translateX(-50%); z-index: 1000; padding: 15px; border-radius: 8px; background-color: rgba(76, 175, 80, 0.9); color: white; font-weight: bold;';
    
    // 将提示添加到页面
    document.body.appendChild(successAlert);
    
    // 1秒后刷新页面
    setTimeout(function() {
        window.location.href = window.location.href.split('?')[0];
    }, 1000);
}

// 检查是否需要显示成功消息
window.addEventListener('DOMContentLoaded', function() {
    <?php if ($show_success_message): ?>
        showSuccessAndRefresh();
    <?php endif; ?>
});
</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>学生对教师的感想留言卡</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Microsoft YaHei', sans-serif;
            background: linear-gradient(135deg, #ff4d4d, #cc0000);
            color: white;
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px 20px;
            position: relative;
            z-index: 10;
        }
        
        .page-title {
            text-align: center;
            font-size: 36px;
            margin-bottom: 40px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .form-section, .display-section {
            background-color: rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 40px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .section-title {
            font-size: 28px;
            margin-bottom: 20px;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-size: 18px;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            background-color: rgba(255, 255, 255, 0.9);
            color: #333;
        }
        
        textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .submit-btn {
            background-color: #ff3333;
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 18px;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s;
            display: block;
            margin: 0 auto;
            width: 200px;
        }
        
        .submit-btn:hover {
            background-color: #cc0000;
        }
        
        .teacher-section {
            margin-bottom: 40px;
        }
        
        .teacher-title {
            font-size: 32px;
            margin-bottom: 20px;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
            text-align: center;
        }
        
        .teacher-box {
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 8px;
            padding: 20px;
            min-height: 80px;
            color: #333;
            font-size: 18px;
            box-shadow: inset 0 0 10px rgba(0, 0, 0, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.5);
        }
        
        .message-item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .message-item:last-child {
            border-bottom: none;
        }
        
        .student-name {
            font-weight: bold;
            color: #cc0000;
        }
        
        .message-content {
            margin-top: 5px;
        }
        
        .no-message {
            color: #666;
            font-style: italic;
            text-align: center;
            padding: 20px;
        }
        
        .footer {
            text-align: center;
            margin-top: 60px;
            padding: 20px;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
            position: relative;
            z-index: 10;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .alert-success {
            background-color: rgba(76, 175, 80, 0.8);
        }
        
        .alert-error {
            background-color: rgba(244, 67, 54, 0.8);
        }
        
        /* 樱花样式 */
        .sakura {
            position: absolute;
            width: 20px;
            height: 20px;
            background: radial-gradient(circle at 30% 30%, #ffebee, #f8bbd0);
            border-radius: 50% 50% 50% 0;
            transform: rotate(-45deg);
            z-index: 1;
            opacity: 0.8;
            filter: blur(0.5px);
        }
        
        /* 樱花动画 */
        @keyframes fall {
            0% {
                transform: translateY(-10vh) rotate(0deg);
                opacity: 0.8;
            }
            100% {
                transform: translateY(100vh) rotate(360deg);
                opacity: 0;
            }
        }
    </style>
</head>
<body>
    <!-- 樱花容器 -->
    <div id="sakura-container"></div>
    
    <div class="container">
        <h1 class="page-title">学生对教师的感想留言卡</h1>
        
        <!-- 提交信息表单 -->
        <div class="form-section">
            <h2 class="section-title">提交信息给老师</h2>
            
            <?php if (isset($success_msg)): ?>
                <div class="alert alert-success"><?php echo $success_msg; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error_msg)): ?>
                <div class="alert alert-error"><?php echo $error_msg; ?></div>
            <?php endif; ?>
            
            <form method="post" action="">
                <div class="form-group">
                    <label for="student_name">您的姓名（可以不填真名）：</label>
                    <input type="text" id="student_name" name="student_name" required>
                </div>
                
                <div class="form-group">
                    <label for="teacher_subject">选择老师：</label>
                    <select id="teacher_subject" name="teacher_subject" required>
                        <option value="">请选择老师科目</option>
                        <option value="语文">语文老师</option>
                        <option value="数学">数学老师</option>
                        <option value="英语">英语老师</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="message">留言内容：</label>
                    <textarea id="message" name="message" placeholder="请输入您对老师的感想..." required></textarea>
                </div>
                
                <button type="submit" name="submit" class="submit-btn">提交信息</button>
            </form>
        </div>
        
        <!-- 教师信息展示 -->
        <div class="display-section">
            <h2 class="section-title">教师信息展示</h2>
            
            <!-- 语文老师部分 -->
            <div class="teacher-section">
                <h1 class="teacher-title">语文老师:</h1>
                <div class="teacher-box">
                    <?php if (!empty($teacher_messages['语文'])): ?>
                        <?php foreach ($teacher_messages['语文'] as $message): ?>
                            <div class="message-item">
                                <div class="student-name"><?php echo $message['student_name']; ?>：</div>
                                <div class="message-content"><?php echo $message['message']; ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-message">暂无学生留言</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- 数学老师部分 -->
            <div class="teacher-section">
                <h1 class="teacher-title">数学老师:</h1>
                <div class="teacher-box">
                    <?php if (!empty($teacher_messages['数学'])): ?>
                        <?php foreach ($teacher_messages['数学'] as $message): ?>
                            <div class="message-item">
                                <div class="student-name"><?php echo $message['student_name']; ?>：</div>
                                <div class="message-content"><?php echo $message['message']; ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-message">暂无学生留言</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- 英语老师部分 -->
            <div class="teacher-section">
                <h1 class="teacher-title">英语老师:</h1>
                <div class="teacher-box">
                    <?php if (!empty($teacher_messages['英语'])): ?>
                        <?php foreach ($teacher_messages['英语'] as $message): ?>
                            <div class="message-item">
                                <div class="student-name"><?php echo $message['student_name']; ?>：</div>
                                <div class="message-content"><?php echo $message['message']; ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-message">暂无学生留言</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="footer">
            by--哔哩哔哩███████，请同学们积极发表自己对老师的感想，这个网站就是大家的心灵树洞！发表方法：在网站上方提交
        </div>
    </div>
    
    <script>
        // 创建樱花飘落效果
        function createSakura() {
            const container = document.getElementById('sakura-container');
            const sakuraCount = 30; // 樱花数量
            
            for (let i = 0; i < sakuraCount; i++) {
                const sakura = document.createElement('div');
                sakura.classList.add('sakura');
                
                // 随机位置
                const left = Math.random() * 100;
                sakura.style.left = `${left}vw`;
                
                // 随机大小
                const size = 5 + Math.random() * 15;
                sakura.style.width = `${size}px`;
                sakura.style.height = `${size}px`;
                
                // 随机动画延迟和持续时间
                const delay = Math.random() * 10;
                const duration = 10 + Math.random() * 20;
                
                sakura.style.animation = `fall ${duration}s linear ${delay}s infinite`;
                
                container.appendChild(sakura);
            }
        }
        
        // 页面加载完成后创建樱花
        window.addEventListener('DOMContentLoaded', createSakura);
    </script>
</body>
</html>