<?php
session_start();
include 'db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $email = $_POST['email'];
    // ตรวจสอบว่าชื่อผู้ใช้ซ้ำหรือไม่
    $check_query = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $check_query->bind_param("s", $username);
    $check_query->execute();
    $result = $check_query->get_result();
    if ($result->num_rows > 0) {
        $error = "ชื่อผู้ใช้นี้มีอยู่แล้ว";
    } else {
        // เพิ่มผู้ใช้ใหม่
        $query = $conn->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
        $query->bind_param("sss", $username, $password, $email);
        if ($query->execute()) {
            $_SESSION['success'] = "สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ";
            header("Location: login.php");
            exit();
        } else {
            $error = "เกิดข้อผิดพลาดในการสมัครสมาชิก";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก - เกมทายศัพท์</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'thai': ['Sarabun', 'sans-serif']
                    },
                    animation: {
                        'bounce-slow': 'bounce 2s infinite',
                        'pulse-slow': 'pulse 3s infinite',
                        'float': 'float 3s ease-in-out infinite',
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-10px)' },
                        }
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        .letter-tile {
            background: linear-gradient(145deg, #f3f4f6, #e5e7eb);
            box-shadow: 4px 4px 8px #d1d5db, -4px -4px 8px #ffffff;
        }
        .floating-letters {
            position: absolute;
            font-size: 2rem;
            color: rgba(99, 102, 241, 0.1);
            animation: float 4s ease-in-out infinite;
            pointer-events: none;
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-purple-400 via-pink-400 to-indigo-500 font-thai relative overflow-hidden">
    <!-- Floating Background Letters -->
    <div class="floating-letters" style="top: 10%; left: 10%; animation-delay: 0s;">ก</div>
    <div class="floating-letters" style="top: 20%; right: 15%; animation-delay: 1s;">ข</div>
    <div class="floating-letters" style="top: 60%; left: 5%; animation-delay: 2s;">ค</div>
    <div class="floating-letters" style="top: 70%; right: 10%; animation-delay: 3s;">ง</div>
    <div class="floating-letters" style="top: 40%; left: 85%; animation-delay: 1.5s;">จ</div>
    <div class="floating-letters" style="top: 80%; left: 75%; animation-delay: 2.5s;">ฉ</div>
    
    <div class="flex items-center justify-center min-h-screen p-4 relative z-10">
        <div class="w-full max-w-md">
            <!-- Logo/Header Section -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-full shadow-lg mb-4 animate-bounce-slow">
                    <span class="text-3xl font-bold text-purple-600">🏰</span>
                </div>
                <h1 class="text-4xl font-bold text-white mb-2 drop-shadow-lg">เกมทายศัพท์</h1>
                <p class="text-purple-100 text-lg">สร้างบัญชีเพื่อเริ่มเล่น</p>
            </div>

            <!-- Registration Form -->
            <div class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-2xl p-8 border border-white/20">
                <div class="text-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">สมัครสมาชิก</h2>
                    <div class="flex justify-center space-x-2">
                        <div class="letter-tile w-8 h-8 rounded-lg flex items-center justify-center font-bold text-purple-600 text-sm">🔮</div>
                        <div class="letter-tile w-8 h-8 rounded-lg flex items-center justify-center font-bold text-pink-600 text-sm">🧙</div>
                        <div class="letter-tile w-8 h-8 rounded-lg flex items-center justify-center font-bold text-indigo-600 text-sm">🧹</div>
                    </div>
                </div>

                <form method="POST" action="" class="space-y-6">
                    <?php if (!empty($error)): ?>
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg animate-pulse">
                            <div class="flex items-center">
                                <span class="text-xl mr-2">⚠️</span>
                                <span><?php echo htmlspecialchars($error); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="space-y-4">
                        <div class="group">
                            <label for="username" class="block text-sm font-semibold text-gray-700 mb-2">
                                🎮 ชื่อผู้ใช้
                            </label>
                            <input type="text" 
                                   class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:ring-0 transition-all duration-300 bg-gray-50 focus:bg-white focus:shadow-lg" 
                                   id="username" 
                                   name="username" 
                                   placeholder="กรอกชื่อผู้ใช้ของคุณ"
                                   required>
                        </div>

                        <div class="group">
                            <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                                🔒 รหัสผ่าน
                            </label>
                            <input type="password" 
                                   class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:ring-0 transition-all duration-300 bg-gray-50 focus:bg-white focus:shadow-lg" 
                                   id="password" 
                                   name="password" 
                                   placeholder="กรอกรหัสผ่านของคุณ"
                                   required>
                        </div>

                        <div class="group">
                            <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                                📧 อีเมล
                            </label>
                            <input type="email" 
                                   class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:ring-0 transition-all duration-300 bg-gray-50 focus:bg-white focus:shadow-lg" 
                                   id="email" 
                                   name="email" 
                                   placeholder="กรอกอีเมลของคุณ">
                        </div>
                    </div>

                    <div class="space-y-4">
                        <button type="submit" 
                                class="w-full bg-gradient-to-r from-purple-600 to-pink-600 text-white font-bold py-3 px-6 rounded-xl hover:from-purple-700 hover:to-pink-700 transform hover:scale-105 transition-all duration-300 shadow-lg hover:shadow-xl">
                            ✨ สมัครสมาชิก
                        </button>

                        <div class="text-center">
                            <span class="text-gray-600">มีบัญชีอยู่แล้ว? </span>
                            <a href="login.php" 
                               class="text-purple-600 hover:text-purple-800 font-semibold hover:underline transition-all duration-300">
                                เข้าสู่ระบบ
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Fun Facts Section -->
            <div class="text-center mt-6">
                <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 border border-white/30">
                    <p class="text-white text-sm">
                        💡 <strong>รู้หรือไม่?</strong> ภาษาไทยมีตัวอักษร 44 ตัว!
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Decorative Elements -->
    <div class="absolute bottom-0 left-0 w-full h-32 bg-gradient-to-t from-black/10 to-transparent pointer-events-none"></div>
    
    <script>
        // Add some interactive animations
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('animate-pulse-slow');
                });
                input.addEventListener('blur', function() {
                    this.parentElement.classList.remove('animate-pulse-slow');
                });
            });
        });
    </script>
</body>
</html>