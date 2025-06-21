<?php
session_start();
include 'db.php';

if (!isset($_POST['word_id']) || !isset($_POST['answer'])) {
    die("ข้อมูลไม่ถูกต้อง");
}

// กรองข้อมูลที่รับมา
$word_id = intval($_POST['word_id']);
$answer = $conn->real_escape_string($_POST['answer']);
$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

// ตรวจสอบว่าคำถามมีอยู่จริงในฐานข้อมูล
$query = $conn->prepare("SELECT * FROM words WHERE id = ?");
$query->bind_param("i", $word_id);
$query->execute();
$result = $query->get_result();
$word = $result->fetch_assoc();

if (!$word) {
    die("ไม่พบคำศัพท์ที่ระบุ");
}

// เก็บคะแนนปัจจุบันก่อนที่คำตอบจะถูกตรวจสอบ
$current_score = $_SESSION['score'] ?? 0;

// ดึงคะแนนสูงสุดครั้งก่อนจากฐานข้อมูล
$high_score = 0;
if ($user_id > 0) {
    $score_query = $conn->prepare("SELECT MAX(score) as high_score FROM scores WHERE user_id = ?");
    $score_query->bind_param("i", $user_id);
    $score_query->execute();
    $score_result = $score_query->get_result();
    $score_data = $score_result->fetch_assoc();
    $high_score = $score_data['high_score'] ?? 0;
}

// ตรวจสอบคำตอบ
if ($answer === $word['correct_translation']) {
    // ถ้าคำตอบถูกต้อง เพิ่มคะแนน
    $_SESSION['score'] = $current_score + 1;
    header("Location: index.php");
    exit();
} else {
    // ถ้าคำตอบผิด
    $message = "คุณตอบผิด!";
    $score = $current_score;  // แสดงคะแนนก่อนที่จะตอบผิด
    
    // บันทึกคะแนนลงฐานข้อมูลถ้าคะแนนมากกว่าครั้งก่อน
    if ($user_id > 0 && $current_score > $high_score) {
        $insert_query = $conn->prepare("INSERT INTO scores (user_id, score, date_played) VALUES (?, ?, NOW())");
        $insert_query->bind_param("ii", $user_id, $current_score);
        $insert_query->execute();
    }
    
    // รีเซ็ตคะแนน
    $_SESSION['score'] = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oxford Game - คุณตอบผิด</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'oxford-blue': '#002147',
                        'oxford-gold': '#FFD700',
                        'book-brown': '#8B4513',
                        'parchment': '#F4E4BC'
                    },
                    fontFamily: {
                        'serif': ['Playfair Display', 'serif'],
                        'mono': ['Courier New', 'monospace']
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    <style>
        .book-shadow {
            box-shadow: 0 10px 20px rgba(220, 38, 127, 0.4);
        }
        .shake-animation {
            animation: shake 0.82s cubic-bezier(.36,.07,.19,.97) both;
        }
        @keyframes shake {
            10%, 90% { transform: translate3d(-1px, 0, 0); }
            20%, 80% { transform: translate3d(2px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
            40%, 60% { transform: translate3d(4px, 0, 0); }
        }
        .fade-in {
            animation: fadeIn 0.8s ease-in-out;
        }
        @keyframes fadeIn {
            0% { opacity: 0; transform: translateY(20px); }
            100% { opacity: 1; transform: translateY(0px); }
        }
        .gradient-text {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .wrong-gradient {
            background: linear-gradient(135deg, #FF6B6B, #FF8E53);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-red-900 via-pink-900 to-purple-900 relative overflow-x-hidden">
    
    <!-- Animated Background Pattern -->
    <div class="absolute inset-0 opacity-20">
        <div class="absolute top-10 left-10 w-16 h-16 border-2 border-red-400 rounded-full animate-pulse sm:w-20 sm:h-20"></div>
        <div class="absolute top-40 right-20 w-12 h-12 border-2 border-pink-400 transform rotate-45 animate-spin sm:w-16 sm:h-16"></div>
        <div class="absolute bottom-20 left-1/4 w-10 h-10 border-2 border-red-300 rounded-full animate-bounce sm:w-12 sm:h-12"></div>
        <div class="absolute bottom-40 right-10 w-20 h-20 border-2 border-pink-300 transform rotate-12 animate-pulse sm:w-24 sm:h-24"></div>
        <div class="absolute top-1/2 left-1/2 w-24 h-24 border border-red-200 rounded-full animate-ping sm:w-32 sm:h-32"></div>
    </div>

    <!-- Main Container -->
    <div class="container mx-auto px-4 py-6 sm:py-8 text-center min-h-screen flex flex-col justify-center">
        
        <!-- Wrong Answer Alert -->
        <div class="mb-8 sm:mb-12 fade-in">
            <div class="shake-animation max-w-[90%] sm:max-w-2xl mx-auto">
                <!-- Large X Symbol -->
                <div class="text-4xl sm:text-6xl mb-4 sm:mb-6">
                    <span class="text-red-400 drop-shadow-2xl">❌</span>
                </div>
                
                <!-- Main Message -->
                <div class="bg-gradient-to-r from-red-600 to-pink-600 p-4 sm:p-6 rounded-2xl book-shadow border-2 sm:border-4 border-red-400 mb-6 sm:mb-8">
                    <h1 class="text-2xl sm:text-4xl md:text-5xl font-serif font-bold wrong-gradient mb-3 sm:mb-4">
                        <?php echo $message; ?>
                    </h1>
                    <div class="w-24 sm:w-32 h-1 bg-red-300 mx-auto mb-4 sm:mb-6"></div>
                    <p class="text-base sm:text-lg md:text-xl text-red-100 font-light mb-4">
                        คำตอบของคุณไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง
                    </p>
                    
                    <!-- Score Display -->
                    <div class="bg-black bg-opacity-30 rounded-xl p-4 sm:p-6 mt-4 sm:mt-6 backdrop-blur-sm">
                        <p class="text-lg sm:text-xl md:text-2xl font-bold text-yellow-300">
                            คะแนนก่อนหน้า: <span class="text-oxford-gold text-xl sm:text-2xl md:text-3xl"><?php echo $score; ?></span>
                        </p>
                        <p class="text-sm sm:text-base md:text-lg text-red-200 mt-2">
                            คะแนนปัจจุบัน: <span class="text-red-400 font-bold">0</span>
                        </p>
                        <?php if ($score > $high_score): ?>
                        <p class="text-sm sm:text-base md:text-lg text-green-200 mt-2">
                            สร้างสถิติใหม่! คะแนน <?php echo $score; ?> ถูกบันทึกลงฐานข้อมูล
                        </p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Correct Answer Display -->
                <div class="bg-gradient-to-r from-green-600 to-emerald-600 p-4 sm:p-6 rounded-xl book-shadow border-2 border-green-400 mb-6 sm:mb-8">
                    <h3 class="text-lg sm:text-xl md:text-2xl font-semibold text-white mb-2">💡 คำตอบที่ถูกต้อง</h3>
                    <p class="text-xl sm:text-2xl md:text-3xl font-bold text-green-100">
                        "<?php echo $word['word']; ?>" = "<?php echo $word['correct_translation']; ?>"
                    </p>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 sm:gap-6 justify-center items-center max-w-[90%] sm:max-w-xl mx-auto fade-in">
            <a href="index.php" 
               class="group relative w-full sm:w-64 md:w-80 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white px-6 sm:px-8 py-4 sm:py-6 rounded-xl font-semibold text-base sm:text-lg md:text-xl transition-all duration-300 shadow-lg hover:shadow-2xl transform hover:scale-105 hover:-translate-y-2 book-shadow">
                <div class="absolute inset-0 bg-gradient-to-r from-oxford-gold to-yellow-400 rounded-xl opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                <span class="relative z-10">🎮 กลับไปเล่นเกม</span>
                <div class="absolute -top-2 -right-2 w-5 sm:w-6 h-5 sm:h-6 bg-oxford-gold rounded-full opacity-0 group-hover:opacity-100 transition-all duration-300 transform scale-0 group-hover:scale-100"></div>
            </a>

            <a href="logout.php" 
               class="group relative w-full sm:w-64 md:w-80 bg-gradient-to-r from-red-600 to-pink-600 hover:from-red-500 hover:to-pink-500 text-white px-6 sm:px-8 py-4 sm:py-6 rounded-xl font-semibold text-base sm:text-lg md:text-xl transition-all duration-300 shadow-lg hover:shadow-2xl transform hover:scale-105 hover:-translate-y-2 book-shadow">
                <div class="absolute inset-0 bg-gradient-to-r from-red-300 to-pink-300 rounded-xl opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                <span class="relative z-10">🚪 ออกจากระบบ</span>
                <div class="absolute -top-2 -right-2 w-5 sm:w-6 h-5 sm:h-6 bg-red-300 rounded-full opacity-0 group-hover:opacity-100 transition-all duration-300 transform scale-0 group-hover:scale-100"></div>
            </a>
        </div>

        <!-- Motivational Message -->
        <div class="mt-8 sm:mt-12 fade-in">
            <div class="bg-black bg-opacity-20 backdrop-blur-sm rounded-xl p-4 sm:p-6 max-w-[90%] sm:max-w-lg mx-auto">
                <h3 class="text-lg sm:text-xl md:text-2xl font-semibold text-yellow-300 mb-3 sm:mb-4">💪 อย่าท้อแท้!</h3>
                <p class="text-sm sm:text-base md:text-lg text-gray-200">
                    การเรียนรู้ต้องผ่านความผิดพลาด ลองใหม่และพัฒนาตัวเองต่อไป!
                </p>
                <div class="flex justify-center space-x-2 mt-3 sm:mt-4">
                    <span class="text-xl sm:text-2xl">⭐</span>
                    <span class="text-xl sm:text-2xl">⭐</span>
                    <span class="text-xl sm:text-2xl">⭐</span>
                </div>
            </div>
        </div>

    </div>

    <!-- JavaScript for Enhanced Interactions -->
    <script>
        // Auto redirect after 10 seconds (optional)
        let countdown = 20;
        const countdownElement = document.createElement('div');
        countdownElement.className = 'fixed bottom-4 sm:bottom-6 left-4 sm:left-6 bg-black bg-opacity-50 text-white px-3 sm:px-4 py-2 rounded-lg text-sm sm:text-base';
        countdownElement.innerHTML = `เด้งกลับหน้าเกมใน: <span class="font-bold text-yellow-300">${countdown}</span> วินาที`;
        document.body.appendChild(countdownElement);

        const countdownTimer = setInterval(() => {
            countdown--;
            countdownElement.innerHTML = `เด้งกลับหน้าเกมใน: <span class="font-bold text-yellow-300">${countdown}</span> วินาที`;
            
            if (countdown <= 0) {
                clearInterval(countdownTimer);
                window.location.href = 'index.php';
            }
        }, 1000);

        // Add click animation to buttons
        document.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', function(e) {
                // Create ripple effect
                const ripple = document.createElement('div');
                ripple.className = 'absolute inset-0 bg-white opacity-30 rounded-xl transform scale-0';
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.style.transform = 'scale(1)';
                    ripple.style.transition = 'transform 0.3s ease-out';
                }, 10);
                
                setTimeout(() => {
                    ripple.remove();
                }, 300);
            });
        });

        // Stop auto redirect if user interacts
        document.addEventListener('click', () => {
            clearInterval(countdownTimer);
            countdownElement.remove();
        });
    </script>
    
</body>
</html>