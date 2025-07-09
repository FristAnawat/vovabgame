<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// เก็บคะแนนปัจจุบันก่อนตรวจคำตอบ
$current_score = $_SESSION['score'] ?? 0;
$user_id = $_SESSION['user_id'];

// รับระดับจาก POST
$valid_levels = ['ง่าย', 'ปานกลาง', 'ยาก'];
$level = isset($_POST['level']) && in_array($_POST['level'], $valid_levels) ? $_POST['level'] : 'ง่าย';
$level_en = ['ง่าย' => 'easy', 'ปานกลาง' => 'medium', 'ยาก' => 'hard'];

// เริ่มต้นตัวช่วยถ้ายังไม่มี
if (!isset($_SESSION['powerups'])) {
    $_SESSION['powerups'] = ['skip' => true, 'double_score' => true];
}
if (!isset($_SESSION['correct_answers'])) {
    $_SESSION['correct_answers'] = 0;
}

// ดึงคะแนนสูงสุดครั้งก่อนจากฐานข้อมูล
$high_score = 0;
$score_query = $conn->prepare("SELECT MAX(score) as high_score FROM scores WHERE user_id = ?");
$score_query->bind_param("i", $user_id);
$score_query->execute();
$score_result = $score_query->get_result();
$score_data = $score_result->fetch_assoc();
$high_score = $score_data['high_score'] ?? 0;

// ดึงคะแนนส่วนตัว 5 อันดับสุดท้าย
$personal_scores = [];
$personal_query = $conn->prepare("SELECT score, date_played FROM scores WHERE user_id = ? ORDER BY score DESC, date_played DESC LIMIT 5");
$personal_query->bind_param("i", $user_id);
$personal_query->execute();
$personal_result = $personal_query->get_result();
while ($row = $personal_result->fetch_assoc()) {
    $personal_scores[] = $row;
}

// ดึงคะแนนสูงสุดของแต่ละ user Top 5
$top_scores = [];
$top_query = $conn->query("
    SELECT s.max_score, s.latest_date, u.username 
    FROM (
        SELECT user_id, MAX(score) as max_score, MAX(date_played) as latest_date
        FROM scores 
        GROUP BY user_id
    ) s
    JOIN users u ON s.user_id = u.id 
    ORDER BY s.max_score DESC, s.latest_date DESC 
    LIMIT 5
");
while ($row = $top_query->fetch_assoc()) {
    $top_scores[] = [
        'score' => $row['max_score'],
        'date_played' => $row['latest_date'],
        'username' => $row['username']
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $word_id = $_POST['word_id'] ?? '';
    $user_answer = $_POST['answer'] ?? '';
    $powerup = $_POST['powerup'] ?? '';

    // ดึงคำตอบที่ถูกต้องและคำศัพท์จากฐานข้อมูล
    $query = $conn->prepare("SELECT word, correct_translation FROM words WHERE id = ?");
    $query->bind_param("i", $word_id);
    $query->execute();
    $result = $query->get_result();
    $word = $result->fetch_assoc();

    if ($powerup === 'skip' && $_SESSION['powerups']['skip']) {
        // ใช้ตัวช่วยข้าม
        $_SESSION['powerups']['skip'] = false;
        $_SESSION['play_sound'] = 'powerup';
        unset($_SESSION['current_word_id']);
        unset($_SESSION['double_score_active']);
        header("Location: index.php?level=" . urlencode($level_en[$level]));
        exit();
    } elseif ($powerup === 'double_score' && $_SESSION['powerups']['double_score']) {
        // ใช้ตัวช่วยคะแนน x2
        $_SESSION['powerups']['double_score'] = false;
        $_SESSION['double_score_active'] = true;
        $_SESSION['current_word_id'] = $word_id;
        $_SESSION['play_sound'] = 'powerup';
        header("Location: index.php?level=" . urlencode($level_en[$level]));
        exit();
    } elseif ($word && $user_answer === $word['correct_translation']) {
        // ตอบถูก
        $score_increment = ($level === 'ง่าย' ? 1 : ($level === 'ปานกลาง' ? 1 : 1));
        if (isset($_SESSION['double_score_active']) && $_SESSION['double_score_active']) {
            $score_increment *= 2;
        }
        $_SESSION['score'] += $score_increment;
        $_SESSION['correct_answers'] += 1;
        $_SESSION['play_sound'] = 'success';

        // บันทึกคะแนนลงฐานข้อมูล
        $score_query = $conn->prepare("INSERT INTO scores (user_id, score, date_played) VALUES (?, ?, NOW())");
        $score_query->bind_param("ii", $user_id, $_SESSION['score']);
        $score_query->execute();

        // ล้างตัวช่วยคะแนน x2 และ word_id
        unset($_SESSION['double_score_active']);
        unset($_SESSION['current_word_id']);

        // ตรวจสอบว่าตอบถูกครบ 10 ข้อหรือไม่
        if ($_SESSION['correct_answers'] >= 10) {
            $_SESSION['score'] += 0; // 
            // สุ่มตัวช่วยใหม่
            $available_powerups = [];
            if (!$_SESSION['powerups']['skip']) {
                $available_powerups[] = 'skip';
            }
            if (!$_SESSION['powerups']['double_score']) {
                $available_powerups[] = 'double_score';
            }
            if (!empty($available_powerups)) {
                $random_powerup = $available_powerups[array_rand($available_powerups)];
                $_SESSION['powerups'][$random_powerup] = true;
            }
            $_SESSION['correct_answers'] = 0;
        }

        header("Location: index.php?level=" . urlencode($level_en[$level]));
        exit();
    } else {
        // ตอบผิดหรือหมดเวลา
        $_SESSION['hearts'] -= 1;
        $_SESSION['play_sound'] = 'loseHeart';
        $_SESSION['last_word'] = $word['word'];
        $_SESSION['last_answer'] = $user_answer;
        $_SESSION['correct_answer'] = $word['correct_translation'];
        unset($_SESSION['current_word_id']);
        unset($_SESSION['double_score_active']);

        if ($_SESSION['hearts'] <= 0) {
            // บันทึกคะแนนก่อนรีเซ็ต
            if ($current_score > $high_score) {
                $insert_query = $conn->prepare("INSERT INTO scores (user_id, score, date_played) VALUES (?, ?, NOW())");
                $insert_query->bind_param("ii", $user_id, $current_score);
                $insert_query->execute();
            }
            // รีเซ็ต session
            $final_score = $current_score;
            $_SESSION['score'] = 0;
            $_SESSION['hearts'] = 3;
            $_SESSION['correct_answers'] = 0;
            $_SESSION['powerups'] = ['skip' => true, 'double_score' => true];
        } else {
            header("Location: index.php?level=" . urlencode($level_en[$level]));
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oxford Game - เกมจบ</title>
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
        :root {
            --oxford-blue: #002147;
            --oxford-gold: #FFD700;
            --book-brown: #8B4513;
            --parchment: #F4E4BC;
        }
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
        .slide-in {
            animation: slideIn 0.5s ease-out;
        }
        @keyframes slideIn {
            0% { opacity: 0; transform: translateX(-30px); }
            100% { opacity: 1; transform: translateX(0px); }
        }
        .slide-in-right {
            animation: slideInRight 0.5s ease-out;
        }
        @keyframes slideInRight {
            0% { opacity: 0; transform: translateX(30px); }
            100% { opacity: 1; transform: translateX(0px); }
        }
        .scale-in {
            animation: scaleIn 0.6s ease-out;
        }
        @keyframes scaleIn {
            0% { opacity: 0; transform: scale(0.8); }
            100% { opacity: 1; transform: scale(1); }
        }
        .touch-button {
            -webkit-tap-highlight-color: transparent;
            touch-action: manipulation;
        }
        .touch-button:active {
            transform: scale(0.95);
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
        
        <!-- Game Over Alert -->
        <div class="mb-8 sm:mb-12 fade-in">
            <div class="shake-animation max-w-[90%] sm:max-w-2xl mx-auto">   
                <!-- Main Message -->
                <div class="bg-gradient-to-r from-red-600 to-pink-600 p-4 sm:p-6 rounded-2xl book-shadow border-2 sm:border-4 border-red-400 mb-6 sm:mb-8">
                    <h1 class="text-2xl sm:text-4xl md:text-5xl font-serif font-bold wrong-gradient mb-3 sm:mb-4">
                        เกมจบ!
                    </h1>
                    <div class="w-24 sm:w-32 h-1 bg-red-300 mx-auto mb-4 sm:mb-6"></div>
                    <p class="text-base sm:text-lg md:text-xl text-red-100 font-light mb-4">
                        หัวใจของคุณหมดลง กรุณาลองเล่นใหม่!
                    </p>
                    
                    <!-- Score Display -->
                    <div class="bg-black bg-opacity-30 rounded-xl p-4 sm:p-6 mt-4 sm:mt-6 backdrop-blur-sm">
                        <p class="text-lg sm:text-xl md:text-2xl font-bold text-yellow-300">
                            คะแนนครั้งนี้: <span class martyrs: 'Times New Roman', Times, serif;" class="text-oxford-gold text-xl sm:text-2xl md:text-3xl"><?php echo $final_score; ?></span>
                        </p>
                        <p class="text-sm sm:text-base md:text-lg text-red-200 mt-2">
                            คะแนนรีเซ็ตเป็น: <span class="text-red-400 font-bold">0</span>
                        </p>
                        <?php if ($final_score > $high_score): ?>
                        <p class="text-sm sm:text-base md:text-lg text-green-200 mt-2">
                            🎉 สร้างสถิติใหม่! คะแนน <?php echo $final_score; ?> ถูกบันทึกลงฐานข้อมูล
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Three Column Layout: Personal Scores | Correct Answer | Top Scores -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6 mb-8 sm:mb-12 max-w-[95%] sm:max-w-7xl mx-auto">
            
            <!-- Left Column: Personal Scores -->
            <?php if ($user_id > 0): ?>
            <div class="bg-gradient-to-br from-blue-800 to-indigo-800 rounded-2xl p-4 sm:p-6 book-shadow border-2 border-blue-400 slide-in">
                <h2 class="text-lg sm:text-xl md:text-2xl font-bold text-blue-100 mb-4 sm:mb-6">
                    🏆 คะแนนส่วนตัว
                </h2>
                <div class="space-y-2 sm:space-y-3">
                    <?php if (empty($personal_scores)): ?>
                        <p class="text-blue-200 text-sm sm:text-base">ยังไม่มีคะแนนในระบบ</p>
                    <?php else: ?>
                        <?php foreach ($personal_scores as $index => $ps): ?>
                        <div class="flex items-center justify-between bg-black bg-opacity-20 rounded-lg p-2 sm:p-3 backdrop-blur-sm">
                            <div class="flex items-center">
                                <span class="text-sm sm:text-base font-bold text-blue-300 mr-2 sm:mr-3">
                                    <?php echo $index + 1; ?>.
                                </span>
                                <div>
                                    <p class="text-sm sm:text-base font-semibold text-white">
                                        <?php echo $ps['score']; ?> คะแนน
                                    </p>
                                    <p class="text-xs text-blue-200">
                                        <?php echo date('d/m/Y H:i', strtotime($ps['date_played'])); ?>
                                    </p>
                                </div>
                            </div>
                            <?php if ($index === 0): ?>
                            <span class="text-sm sm:text-base">👑</span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="bg-gradient-to-br from-gray-600 to-gray-800 rounded-2xl p-4 sm:p-6 book-shadow border-2 border-gray-400 slide-in">
                <h2 class="text-lg sm:text-xl md:text-2xl font-bold text-gray-100 mb-4 sm:mb-6">
                    🏆 คะแนนส่วนตัว
                </h2>
                <p class="text-gray-200 text-sm sm:text-base">เข้าสู่ระบบเพื่อดูคะแนนส่วนตัว</p>
            </div>
            <?php endif; ?>
            
            <!-- Center Column: Correct Answer -->
            <div class="bg-gradient-to-br from-green-600 to-emerald-600 rounded-2xl p-4 sm:p-6 book-shadow border-2 border-green-400 scale-in">
                <h2 class="text-lg sm:text-xl md:text-2xl font-bold text-green-100 mb-4 sm:mb-6">
                    💡 คำตอบที่ถูกต้อง
                </h2>
                <div class="bg-black bg-opacity-20 rounded-xl p-4 sm:p-6 backdrop-blur-sm">
                    <div class="text-center">
                        <p class="text-base sm:text-lg md:text-xl text-green-200 mb-2">คำศัพท์:</p>
                        <p class="text-xl sm:text-2xl md:text-3xl font-bold text-white mb-4">
                            "<?php echo htmlspecialchars($_SESSION['last_word']); ?>"
                        </p>
                        <div class="w-16 sm:w-20 h-1 bg-green-300 mx-auto mb-4"></div>
                        <p class="text-base sm:text-lg md:text-xl text-green-200 mb-2">คำตอบของคุณ:</p>
                        <p class="text-xl sm:text-2xl md:text-3xl font-bold text-white mb-4">
                            "<?php echo htmlspecialchars($_SESSION['last_answer'] ?: 'ไม่ได้เลือกคำตอบ'); ?>"
                        </p>
                        <p class="text-base sm:text-lg md:text-xl text-green-200 mb-2">คำแปลที่ถูกต้อง:</p>
                        <p class="text-xl sm:text-2xl md:text-3xl font-bold text-green-100">
                            "<?php echo htmlspecialchars($_SESSION['correct_answer']); ?>"
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Right Column: Top 5 Global Scores -->
            <div class="bg-gradient-to-br from-purple-800 to-pink-800 rounded-2xl p-4 sm:p-6 book-shadow border-2 border-purple-400 slide-in-right">
                <h2 class="text-lg sm:text-xl md:text-2xl font-bold text-purple-100 mb-4 sm:mb-6">
                    🌟 Top 5 คะแนนสูงสุด
                </h2>
                <div class="space-y-2 sm:space-y-3">
                    <?php if (empty($top_scores)): ?>
                        <p class="text-purple-200 text-sm sm:text-base">ยังไม่มีคะแนนในระบบ</p>
                    <?php else: ?>
                        <?php foreach ($top_scores as $index => $ts): ?>
                        <div class="flex items-center justify-between bg-black bg-opacity-20 rounded-lg p-2 sm:p-3 backdrop-blur-sm">
                            <div class="flex items-center">
                                <span class="text-sm sm:text-base font-bold mr-2 sm:mr-3
                                    <?php echo $index === 0 ? 'text-yellow-300' : ($index === 1 ? 'text-gray-300' : ($index === 2 ? 'text-orange-300' : 'text-purple-300')); ?>">
                                    <?php echo $index + 1; ?>.
                                </span>
                                <div>
                                    <p class="text-sm sm:text-base font-semibold text-white">
                                        <?php echo htmlspecialchars($ts['username']); ?>
                                    </p>
                                    <p class="text-xs text-purple-200">
                                        <?php echo $ts['score']; ?> คะแนน • <?php echo date('d/m/Y', strtotime($ts['date_played'])); ?>
                                    </p>
                                </div>
                            </div>
                            <?php if ($index === 0): ?>
                            <span class="text-sm sm:text-base">🥇</span>
                            <?php elseif ($index === 1): ?>
                            <span class="text-sm sm:text-base">🥈</span>
                            <?php elseif ($index === 2): ?>
                            <span class="text-sm sm:text-base">🥉</span>
                            <?php else: ?>
                            <span class="text-sm">⭐</span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 sm:gap-6 justify-center items-center max-w-[90%] sm:max-w-2xl mx-auto fade-in">
            <a href="index.php?level=<?php echo urlencode($level_en[$level]); ?>" 
               class="group relative w-full sm:w-1/3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white px-6 sm:px-8 py-4 sm:py-6 rounded-xl font-semibold text-base sm:text-lg md:text-xl transition-all duration-300 shadow-lg hover:shadow-2xl transform hover:scale-105 hover:-translate-y-2 book-shadow touch-button">
                <div class="absolute inset-0 bg-gradient-to-r from-oxford-gold to-yellow-400 rounded-xl opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                <span class="relative z-10">🎮 เล่นต่อ (<?php echo $level; ?>)</span>
                <div class="absolute -top-2 -right-2 w-5 sm:w-6 h-5 sm:h-6 bg-oxford-gold rounded-full opacity-0 group-hover:opacity-100 transition-all duration-300 transform scale-0 group-hover:scale-100"></div>
            </a>

            <a href="home.php" 
               class="group relative w-full sm:w-1/3 bg-gradient-to-r from-green-600 to-teal-600 hover:from-green-500 hover:to-teal-500 text-white px-6 sm:px-8 py-4 sm:py-6 rounded-xl font-semibold text-base sm:text-lg md:text-xl transition-all duration-300 shadow-lg hover:shadow-2xl transform hover:scale-105 hover:-translate-y-2 book-shadow touch-button">
                <div class="absolute inset-0 bg-gradient-to-r from-oxford-gold to-yellow-400 rounded-xl opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                <span class="relative z-10">🔄 เปลี่ยนระดับ</span>
                <div class="absolute -top-2 -right-2 w-5 sm:w-6 h-5 sm:h-6 bg-oxford-gold rounded-full opacity-0 group-hover:opacity-100 transition-all duration-300 transform scale-0 group-hover:scale-100"></div>
            </a>

            <a href="logout.php" 
               class="group relative w-full sm:w-1/3 bg-gradient-to-r from-red-600 to-pink-600 hover:from-red-500 hover:to-pink-500 text-white px-6 sm:px-8 py-4 sm:py-6 rounded-xl font-semibold text-base sm:text-lg md:text-xl transition-all duration-300 shadow-lg hover:shadow-2xl transform hover:scale-105 hover:-translate-y-2 book-shadow touch-button">
                <div class="absolute inset-0 bg-gradient-to-r from-red-300 to-pink-300 rounded-xl opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                <span class="relative z-10">🚪 ออกจากระบบ</span>
                <div class="absolute -top-2 -right-2 w-5 sm:w-6 h-5 sm:h-6 bg-red-300 rounded-full opacity-0 group-hover:opacity-100 transition-all duration-300 transform scale-0 group-hover:scale-100"></div>
            </a>
        </div>
        
    </div>
    
    <!-- JavaScript for Enhanced Interactions -->
    <script>
        // Animation for leaderboard items
        document.querySelectorAll('.space-y-2 > div, .space-y-3 > div').forEach((item, index) => {
            item.style.animationDelay = `${index * 0.1}s`;
            item.classList.add('slide-in');
        });

        // Sound Manager
        class SoundManager {
            constructor() {
                this.audioContext = null;
                this.sounds = {};
                this.isEnabled = true;
                this.volume = 0.5;
                this.init();
            }

            async init() {
                try {
                    this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
                    this.masterGain = this.audioContext.createGain();
                    this.masterGain.connect(this.audioContext.destination);
                    this.masterGain.gain.value = this.volume;
                    this.createSounds();
                } catch (error) {
                    console.warn('Audio not supported:', error);
                }
            }

            createSounds() {
                this.sounds.click = this.createTone(400, 'triangle', 0.2, 0.15);
                this.sounds.hover = this.createTone(600, 'sine', 0.05, 0.1);
                this.sounds.loseHeart = this.createTone(300, 'sawtooth', 0.3, 0.6);
            }

            createTone(frequency, type, volume, duration) {
                return () => {
                    if (!this.audioContext || !this.isEnabled) return;
                    const oscillator = this.audioContext.createOscillator();
                    const gainNode = this.audioContext.createGain();
                    oscillator.type = type;
                    oscillator.frequency.setValueAtTime(frequency, this.audioContext.currentTime);
                    gainNode.gain.setValueAtTime(0, this.audioContext.currentTime);
                    gainNode.gain.linearRampToValueAtTime(volume, this.audioContext.currentTime + 0.01);
                    gainNode.gain.exponentialRampToValueAtTime(0.01, this.audioContext.currentTime + duration);
                    oscillator.connect(gainNode);
                    gainNode.connect(this.masterGain);
                    oscillator.start();
                    oscillator.stop(this.audioContext.currentTime + duration);
                };
            }

            play(soundName) {
                if (this.sounds[soundName]) {
                    this.sounds[soundName]();
                }
            }

            setVolume(volume) {
                this.volume = volume;
                if (this.masterGain) {
                    this.masterGain.gain.value = volume;
                }
            }

            toggle() {
                this.isEnabled = !this.isEnabled;
                return this.isEnabled;
            }

            resumeContext() {
                if (this.audioContext && this.audioContext.state === 'suspended') {
                    this.audioContext.resume();
                }
            }
        }

        const soundManager = new SoundManager();

        // Touch and hover effects for buttons
        document.querySelectorAll('.touch-button').forEach(button => {
            button.addEventListener('mouseenter', () => {
                soundManager.play('hover');
            });
            button.addEventListener('click', () => {
                soundManager.play('click');
            });
            button.addEventListener('touchstart', (e) => {
                e.preventDefault();
                button.style.transform = 'scale(0.95)';
                soundManager.play('hover');
            });
            button.addEventListener('touchend', (e) => {
                e.preventDefault();
                button.style.transform = '';
                soundManager.play('click');
                window.location.href = button.href;
            });
        });

        // Prevent double-tap zoom
        let lastTouchEnd = 0;
        document.addEventListener('touchend', (event) => {
            const now = new Date().getTime();
            if (now - lastTouchEnd <= 300) {
                event.preventDefault();
            }
            lastTouchEnd = now;
        }, false);

        // Play loseHeart sound on page load
        document.addEventListener('DOMContentLoaded', () => {
            soundManager.play('loseHeart');
        });

        // Resume audio context on user interaction
        document.addEventListener('click', () => {
            soundManager.resumeContext();
        }, { once: true });
    </script>
</body>
</html>