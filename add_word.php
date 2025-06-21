<?php
session_start();
include 'db.php';

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบและเป็น Admin หรือไม่
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("คุณไม่มีสิทธิ์เข้าถึงหน้านี้");
}

// หากส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $word = $conn->real_escape_string($_POST['word']);
    $correct_translation = $conn->real_escape_string($_POST['correct_translation']);
    $wrong_translation = $conn->real_escape_string($_POST['wrong_translation']);

    // เพิ่มคำศัพท์ลงในฐานข้อมูล
    $query = $conn->prepare("INSERT INTO words (word, correct_translation, wrong_translation) VALUES (?, ?, ?)");
    $query->bind_param("sss", $word, $correct_translation, $wrong_translation);
    
    if ($query->execute()) {
        $success = "เพิ่มคำศัพท์เรียบร้อยแล้ว!";
    } else {
        $error = "เกิดข้อผิดพลาดในการเพิ่มคำศัพท์";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มคำศัพท์ - Word Game Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .floating-animation {
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        .pulse-border {
            animation: pulse-border 2s infinite;
        }
        
        @keyframes pulse-border {
            0%, 100% { box-shadow: 0 0 0 0 rgba(147, 197, 253, 0.7); }
            50% { box-shadow: 0 0 0 10px rgba(147, 197, 253, 0); }
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .card-hover {
            transition: all 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-purple-50 via-blue-50 to-indigo-100">
    <!-- Background Pattern -->
    <div class="fixed inset-0 opacity-5">
        <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,<svg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"><g fill="none" fill-rule="evenodd"><g fill="%239C92AC" fill-opacity="0.1"><circle cx="30" cy="30" r="4"/></g></svg>');"></div>
    </div>
    
    <!-- Floating Icons -->
    <div class="fixed top-20 left-10 text-purple-300 opacity-20 floating-animation">
        <i class="fas fa-book text-4xl"></i>
    </div>
    <div class="fixed top-40 right-20 text-blue-300 opacity-20 floating-animation" style="animation-delay: -1s;">
        <i class="fas fa-language text-3xl"></i>
    </div>
    <div class="fixed bottom-40 left-20 text-indigo-300 opacity-20 floating-animation" style="animation-delay: -2s;">
        <i class="fas fa-spell-check text-5xl"></i>
    </div>
    
    <div class="relative z-10 min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-2xl">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="inline-block p-4 bg-gradient-to-r from-purple-500 to-blue-600 rounded-full mb-4 pulse-border">
                    <i class="fas fa-plus-circle text-white text-3xl"></i>
                </div>
                <h1 class="text-4xl font-bold gradient-text mb-2">เพิ่มคำศัพท์ใหม่</h1>
                <p class="text-gray-600">เพิ่มคำศัพท์เพื่อเสริมสร้างเกมทายศัพท์ให้น่าสนใจยิ่งขึ้น</p>
            </div>

            <!-- Main Card -->
            <div class="bg-white/80 backdrop-blur-sm rounded-3xl shadow-2xl p-8 card-hover border border-white/20">
                <!-- Success/Error Messages -->
                <?php if (!empty($success)): ?>
                    <div class="mb-6 p-4 bg-gradient-to-r from-green-100 to-emerald-100 border border-green-200 rounded-2xl flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-500 text-xl"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-green-700 font-medium"><?php echo $success; ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="mb-6 p-4 bg-gradient-to-r from-red-100 to-pink-100 border border-red-200 rounded-2xl flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-500 text-xl"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-red-700 font-medium"><?php echo $error; ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Form -->
                <form method="POST" action="" class="space-y-6">
                    <!-- Word Input -->
                    <div class="space-y-2">
                        <label for="word" class="flex items-center text-sm font-semibold text-gray-700">
                            <i class="fas fa-font mr-2 text-purple-500"></i>
                            คำศัพท์
                        </label>
                        <div class="relative">
                            <input type="text" 
                                   class="w-full px-4 py-3 pl-12 bg-gray-50 border-2 border-gray-200 rounded-2xl focus:border-purple-400 focus:ring-4 focus:ring-purple-100 transition-all duration-300 text-gray-700 placeholder-gray-400" 
                                   id="word" 
                                   name="word" 
                                   placeholder="เช่น Hello, Beautiful, ฯลฯ"
                                   required>
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fas fa-edit text-gray-400"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Correct Translation -->
                    <div class="space-y-2">
                        <label for="correct_translation" class="flex items-center text-sm font-semibold text-gray-700">
                            <i class="fas fa-check mr-2 text-green-500"></i>
                            คำแปลที่ถูกต้อง
                        </label>
                        <div class="relative">
                            <input type="text" 
                                   class="w-full px-4 py-3 pl-12 bg-gray-50 border-2 border-gray-200 rounded-2xl focus:border-green-400 focus:ring-4 focus:ring-green-100 transition-all duration-300 text-gray-700 placeholder-gray-400" 
                                   id="correct_translation" 
                                   name="correct_translation" 
                                   placeholder="คำแปลที่ถูกต้อง"
                                   required>
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fas fa-check-circle text-gray-400"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Wrong Translation -->
                    <div class="space-y-2">
                        <label for="wrong_translation" class="flex items-center text-sm font-semibold text-gray-700">
                            <i class="fas fa-times mr-2 text-red-500"></i>
                            คำแปลที่ผิด
                        </label>
                        <div class="relative">
                            <input type="text" 
                                   class="w-full px-4 py-3 pl-12 bg-gray-50 border-2 border-gray-200 rounded-2xl focus:border-red-400 focus:ring-4 focus:ring-red-100 transition-all duration-300 text-gray-700 placeholder-gray-400" 
                                   id="wrong_translation" 
                                   name="wrong_translation" 
                                   placeholder="คำแปลที่ผิด (สำหรับเป็นตัวเลือกล่อลวง)"
                                   required>
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fas fa-times-circle text-gray-400"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-4 pt-6">
                        <button type="submit" 
                                class="flex-1 bg-gradient-to-r from-purple-500 to-blue-600 hover:from-purple-600 hover:to-blue-700 text-white font-semibold py-3 px-6 rounded-2xl transition-all duration-300 transform hover:scale-105 hover:shadow-lg flex items-center justify-center">
                            <i class="fas fa-plus-circle mr-2"></i>
                            เพิ่มคำศัพท์
                        </button>
                        
                        <a href="index.php" 
                           class="flex-1 bg-gradient-to-r from-gray-100 to-gray-200 hover:from-gray-200 hover:to-gray-300 text-gray-700 font-semibold py-3 px-6 rounded-2xl transition-all duration-300 transform hover:scale-105 hover:shadow-lg flex items-center justify-center border border-gray-300">
                            <i class="fas fa-arrow-left mr-2"></i>
                            กลับหน้าหลัก
                        </a>
                    </div>
                </form>
            </div>

            <!-- Additional Info Card -->
            <div class="mt-6 bg-white/60 backdrop-blur-sm rounded-2xl p-6 border border-white/20">
                <div class="flex items-start space-x-4">
                    <div class="flex-shrink-0">
                        <i class="fas fa-lightbulb text-yellow-500 text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">เคล็ดลับการเพิ่มคำศัพท์</h3>
                        <ul class="text-gray-600 space-y-1 text-sm">
                            <li>• เลือกคำศัพท์ที่มีความหมายชัดเจน</li>
                            <li>• คำแปลที่ผิดควรใกล้เคียงกับคำแปลที่ถูกต้องเพื่อเพิ่มความท้าทาย</li>
                            <li>• ควรเลือกคำศัพท์ที่เหมาะสมกับระดับผู้เล่น</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input[type="text"]');
            
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('transform', 'scale-105');
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.classList.remove('transform', 'scale-105');
                });
            });
        });
    </script>
</body>
</html>