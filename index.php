<?php
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

// ตรวจสอบว่าคอลัมน์ level มีอยู่และกำหนดค่าแล้วหรือไม่
try {
    $check_column = $conn->query("SHOW COLUMNS FROM words LIKE 'level'");
    if ($check_column->num_rows == 0) {
        header("Location: set_level.php");
        exit();
    }

    $check_null = $conn->query("SELECT COUNT(*) as null_count FROM words WHERE level IS NULL");
    if ($check_null->fetch_assoc()['null_count'] > 0) {
        header("Location: set_level.php");
        exit();
    }
} catch (PDOException $e) {
    echo "เกิดข้อผิดพลาด: " . $e->getMessage();
    exit();
}

// กำหนดค่าเริ่มต้นสำหรับ session
if (!isset($_SESSION['score'])) {
    $_SESSION['score'] = 0;
}
if (!isset($_SESSION['hearts'])) {
    $_SESSION['hearts'] = 3;
}
if (!isset($_SESSION['powerups'])) {
    $_SESSION['powerups'] = ['skip' => true, 'double_score' => true];
}
if (!isset($_SESSION['correct_answers'])) {
    $_SESSION['correct_answers'] = 0;
}

// ดึงคะแนนสูงสุดของผู้ใช้
$user_id = $_SESSION['user_id'];
$score_query = $conn->prepare("SELECT MAX(score) as high_score FROM scores WHERE user_id = ?");
$score_query->bind_param("i", $user_id);
$score_query->execute();
$score_result = $score_query->get_result();
$high_score = $score_result->fetch_assoc()['high_score'] ?? 0;

// รับระดับจาก query parameter
$valid_levels = ['ง่าย', 'ปานกลาง', 'ยาก'];
$level = isset($_GET['level']) && in_array($_GET['level'], ['easy', 'medium', 'hard']) ? $_GET['level'] : 'easy';
$level_th = ['easy' => 'ง่าย', 'medium' => 'ปานกลาง', 'hard' => 'ยาก'];
$level = $level_th[$level]; // แปลงเป็นภาษาไทยสำหรับใช้ใน query

// ตรวจสอบว่ามี word_id จากตัวช่วยคะแนน x2 หรือไม่
$word_id = (isset($_SESSION['current_word_id']) && isset($_SESSION['double_score_active']) && $_SESSION['double_score_active']) ? $_SESSION['current_word_id'] : null;
if ($word_id) {
    $query = $conn->prepare("SELECT * FROM words WHERE id = ?");
    $query->bind_param("i", $word_id);
    $query->execute();
    $word = $query->get_result()->fetch_assoc();
} else {
    // ตรวจสอบจำนวนคำศัพท์ในระดับที่เลือก
    $count_query = $conn->prepare("SELECT COUNT(*) as word_count FROM words WHERE level = ?");
    $count_query->bind_param("s", $level);
    $count_query->execute();
    $word_count = $count_query->get_result()->fetch_assoc()['word_count'];

    if ($word_count == 0) {
        // ถ้าไม่มีคำศัพท์ในระดับนี้ เปลี่ยนเป็นระดับง่าย
        $level = 'ง่าย';
        $query = $conn->prepare("SELECT * FROM words WHERE level = ? ORDER BY RAND() LIMIT 1");
        $query->bind_param("s", $level);
    } else {
        // สุ่มคำศัพท์ตามระดับ
        $query = $conn->prepare("SELECT * FROM words WHERE level = ? ORDER BY RAND() LIMIT 1");
        $query->bind_param("s", $level);
    }

    $query->execute();
    $word = $query->get_result()->fetch_assoc();
    $_SESSION['current_word_id'] = $word['id'];
}

// สุ่มตำแหน่งคำแปล
$translations = [
    ['value' => $word['correct_translation'], 'is_correct' => true],
    ['value' => $word['wrong_translation'], 'is_correct' => false]
];
shuffle($translations);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Oxford Game</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        if (typeof tailwind !== 'undefined') {
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

        .score-display {
            background: linear-gradient(135deg, var(--oxford-gold), #FFA500);
            color: var(--oxford-blue);
            border: 2px solid var(--oxford-blue);
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
            font-weight: 700;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
        }

        .leaderboard-button {
            background: var(--oxford-gold);
            color: var(--oxford-blue);
        }

        .leaderboard-title, .leaderboard-score {
            color: var(--oxford-gold);
        }

        .book-shadow {
            box-shadow: 0 8px 25px rgba(0, 33, 71, 0.3);
        }

        .floating-animation {
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-8px); }
        }

        .gradient-text {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .powerup-button {
            background: linear-gradient(135deg, #FF6B6B, #FF8E53);
            color: white;
            border: 2px solid #FF4444;
            transition: all 0.3s ease;
        }

        .powerup-button:hover {
            background: linear-gradient(135deg, #FF8E53, #FF6B6B);
            transform: scale(1.05);
        }

        .powerup-button:disabled {
            background: gray;
            border-color: gray;
            cursor: not-allowed;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .mobile-padding {
                padding-left: 1rem;
                padding-right: 1rem;
            }

            .mobile-button {
                min-height: 60px;
                font-size: 1.1rem;
            }

            .mobile-word {
                font-size: 2.5rem;
            }

            .mobile-title {
                font-size: 2.5rem;
            }
        }

        .touch-button {
            -webkit-tap-highlight-color: transparent;
            touch-action: manipulation;
        }

        .touch-button:active {
            transform: scale(0.95);
        }

        .timer {
            position: fixed;
            top: 5%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 2rem;
            font-weight: bold;
            color: var(--oxford-gold);
        }

        .timer.urgent {
            color: #ff4444;
            font-size: 2.5rem;
            text-shadow: 0 0 30px rgba(255,68,68,0.8);
            border-color: #ff4444;
            background: rgba(255, 68, 68, 0.1);
            animation: pulse 0.5s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: translate(-50%, -50%) scale(1); }
            50% { transform: translate(-50%, -50%) scale(1.1); }
        }

        .sound-control {
            position: fixed;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            background: rgba(0, 33, 71, 0.8);
            color: var(--oxford-gold);
            border: 2px solid var(--oxford-gold);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .sound-control:hover {
            background: rgba(255, 215, 0, 0.2);
            transform: translateY(-50%) scale(1.1);
        }

        .sound-control.muted {
            background: rgba(139, 69, 19, 0.8);
            color: #888;
            border-color: #888;
        }

        .volume-slider {
            position: fixed;
            top: 60%;
            right: 10px;
            width: 80px;
            transform: rotate(-90deg);
            z-index: 1000;
        }

        .volume-slider input[type="range"] {
            width: 100%;
            height: 5px;
            background: #ddd;
            outline: none;
            border-radius: 5px;
        }

        .volume-slider input[type="range"]::-webkit-slider-thumb {
            appearance: none;
            width: 15px;
            height: 15px;
            background: var(--oxford-gold);
            border-radius: 50%;
            cursor: pointer;
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-oxford-blue via-blue-900 to-indigo-900 relative overflow-x-hidden">
    <!-- Sound Control Button -->
    <div class="sound-control" id="soundToggle" title="เปิด/ปิดเสียง">🔊</div>

    <!-- Volume Slider -->
    <div class="volume-slider" id="volumeSlider">
        <input type="range" min="0" max="100" value="50" id="volumeControl">
    </div>

    <!-- Background Pattern -->
    <div class="absolute inset-0 opacity-5 md:opacity-10">
        <div class="absolute top-5 left-5 w-12 h-12 md:w-20 md:h-20 border-2 border-oxford-gold rounded-full"></div>
        <div class="absolute top-20 right-10 w-8 h-8 md:w-16 md:h-16 border-2 border-oxford-gold transform rotate-45"></div>
        <div class="absolute bottom-20 left-1/4 w-6 h-6 md:w-12 md:h-12 border-2 border-oxford-gold rounded-full"></div>
        <div class="absolute bottom-40 right-5 w-10 h-10 md:w-24 md:h-24 border-2 border-oxford-gold transform rotate-12"></div>
    </div>

    <!-- Score and Hearts Display -->
    <div class="fixed top-4 left-4 z-50">
        <div class="score-display px-4 py-3 md:px-6 md:py-4 rounded-xl shadow-lg">
            <div class="text-sm md:text-base font-bold">คะแนนปัจจุบัน: <?php echo $_SESSION['score']; ?></div>
            <div class="text-sm md:text-base font-bold">คะแนนสูงสุด: <?php echo $high_score; ?></div>
            <div class="text-sm md:text-base font-bold flex items-center">
                หัวใจ: 
                <?php for ($i = 0; $i < $_SESSION['hearts']; $i++): ?>
                    <span class="text-red-500 text-lg md:text-xl">❤️</span>
                <?php endfor; ?>
            </div>
            <div class="text-sm md:text-base font-bold">คำถามที่ตอบถูก: <?php echo $_SESSION['correct_answers']; ?> / 10</div>
        </div>
    </div>

    <!-- Leaderboard Button -->
    <div class="fixed top-4 right-4 z-50">
        <a href="leaderboard.php" 
           class="group bg-gradient-to-r from-yellow-500 to-orange-500 hover:from-yellow-400 hover:to-orange-400 text-white px-4 py-2 rounded-full font-semibold transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105 flex items-center space-x-2">
            <span class="text-lg">🏆</span>
            <span class="hidden sm:inline">อันดับคะแนน</span>
            <span class="sm:hidden">อันดับ</span>
        </a>
    </div>

    <!-- Timer Display -->
    <div class="timer" id="timer">10</div>

    <!-- Main Container -->
    <div class="container mx-auto mobile-padding pt-20 pb-6 text-center min-h-screen flex flex-col justify-center">
        <!-- Header Section -->
        <div class="mb-8 md:mb-12 floating-animation">
            <h1 class="mobile-title md:text-6xl lg:text-7xl font-serif font-bold gradient-text mb-2 md:mb-4">
                Oxford Game
            </h1>
            <p class="text-sm md:text-xl text-gray-200 font-light px-4">
                ทายคำศัพท์ให้ถูกต้อง เพิ่มพูนความรู้ภาษาอังกฤษ (ระดับ: <?php echo $level; ?>)
            </p>
        </div>

        <!-- Word Display -->
        <div class="mb-8 md:mb-12">
            <div class="bg-gradient-to-r from-parchment to-yellow-100 text-oxford-blue p-4 md:p-8 rounded-2xl book-shadow mx-auto border-2 md:border-4 border-oxford-gold">
                <div class="bg-white bg-opacity-50 rounded-xl p-4 md:p-6 backdrop-blur-sm">
                    <h2 class="mobile-word md:text-5xl lg:text-6xl font-serif font-bold mb-1 md:mb-2 break-words" id="wordDisplay">
                        <?php echo $word['word']; ?>
                    </h2>
                    <div class="w-12 md:w-24 h-1 bg-oxford-blue mx-auto"></div>
                </div>
            </div>
        </div>

        <!-- Answer Choices -->
        <form id="answerForm" method="POST" action="check_answer.php" class="mb-8 md:mb-12">
            <input type="hidden" name="word_id" value="<?php echo $word['id']; ?>">
            <input type="hidden" name="level" value="<?php echo $level; ?>">
            <div class="flex flex-col gap-4 md:gap-6 justify-center items-center max-w-md md:max-w-4xl mx-auto">
                <?php foreach ($translations as $index => $translation): ?>
                    <button 
                        type="submit" 
                        name="answer" 
                        value="<?php echo htmlspecialchars($translation['value']); ?>" 
                        class="group relative w-full mobile-button bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 active:from-emerald-700 active:to-teal-700 text-white px-6 md:px-8 py-4 md:py-6 rounded-2xl font-semibold text-lg md:text-xl transition-all duration-200 shadow-lg hover:shadow-2xl transform hover:scale-105 book-shadow touch-button answer-button"
                        data-answer="<?php echo htmlspecialchars($translation['value']); ?>"
                    >
                        <div class="absolute inset-0 bg-gradient-to-r from-oxford-gold to-yellow-400 rounded-2xl opacity-0 group-hover:opacity-20 group-active:opacity-30 transition-opacity duration-200"></div>
                        <span class="relative z-10"><?php echo htmlspecialchars($translation['value']); ?></span>
                        <div class="absolute -top-1 -right-1 md:-top-2 md:-right-2 w-4 h-4 md:w-6 md:h-6 bg-oxford-gold rounded-full opacity-0 group-hover:opacity-100 transition-all duration-200 transform scale-0 group-hover:scale-100"></div>
                    </button>
                <?php endforeach; ?>
            </div>
        </form>

        <!-- Powerup Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center items-center max-w-md md:max-w-4xl mx-auto mb-8">
            <?php if ($_SESSION['powerups']['skip']): ?>
            <form id="skipForm" method="POST" action="check_answer.php">
                <input type="hidden" name="word_id" value="<?php echo $word['id']; ?>">
                <input type="hidden" name="level" value="<?php echo $level; ?>">
                <button 
                    type="submit" 
                    name="powerup" 
                    value="skip" 
                    class="group relative w-full sm:w-48 powerup-button px-4 py-3 rounded-xl font-semibold text-base md:text-lg transition-all duration-200 shadow-lg hover:shadow-2xl transform touch-button"
                >
                    <div class="absolute inset-0 bg-gradient-to-r from-oxford-gold to-yellow-400 rounded-xl opacity-0 group-hover:opacity-20 transition-opacity duration-200"></div>
                    <span class="relative z-10">⏭️ ข้าม</span>
                </button>
            </form>
            <?php endif; ?>
            <?php if ($_SESSION['powerups']['double_score']): ?>
            <form id="doubleScoreForm" method="POST" action="check_answer.php">
                <input type="hidden" name="word_id" value="<?php echo $word['id']; ?>">
                <input type="hidden" name="level" value="<?php echo $level; ?>">
                <button 
                    type="submit" 
                    name="powerup" 
                    value="double_score" 
                    class="group relative w-full sm:w-48 powerup-button px-4 py-3 rounded-xl font-semibold text-base md:text-lg transition-all duration-200 shadow-lg hover:shadow-2xl transform touch-button"
                >
                    <div class="absolute inset-0 bg-gradient-to-r from-oxford-gold to-yellow-400 rounded-xl opacity-0 group-hover:opacity-20 transition-opacity duration-200"></div>
                    <span class="relative z-10">✨ คะแนน x2</span>
                </button>
            </form>
            <?php endif; ?>
        </div>

        <!-- Decorative Elements -->
        <div class="flex justify-center space-x-4 opacity-30">
            <div class="w-1.5 h-1.5 md:w-2 md:h-2 bg-oxford-gold rounded-full animate-ping"></div>
            <div class="w-1.5 h-1.5 md:w-2 md:h-2 bg-oxford-gold rounded-full animate-ping" style="animation-delay: 0.5s;"></div>
            <div class="w-1.5 h-1.5 md:w-2 md:h-2 bg-oxford-gold rounded-full animate-ping" style="animation-delay: 1s;"></div>
        </div>
    </div>

    <!-- Leaderboard Section -->
    <?php
    $leaderboard_query = $conn->query("
        SELECT u.username, MAX(s.score) as score
        FROM scores s
        JOIN users u ON s.user_id = u.id
        GROUP BY u.id, u.username
        ORDER BY score DESC LIMIT 5
    ");
    ?>
    <button id="leaderboardToggle" class="leaderboard-button fixed bottom-4 right-4 md:hidden p-3 rounded-full shadow-lg touch-button z-40">
        🏆
    </button>
    <div id="leaderboardPanel" class="fixed bottom-4 right-4 bg-black bg-opacity-90 backdrop-blur-sm rounded-xl p-4 text-white max-w-xs transform translate-y-full md:translate-y-0 transition-transform duration-300 z-30">
        <div class="leaderboard-header flex justify-between items-center mb-2 md:mb-2">
            <h3 class="text-base md:text-lg font-semibold leaderboard-title">🏆 อันดับคะแนน</h3>
            <button id="closeLeaderboard" class="md:hidden text-gray-400 hover:text-white">✕</button>
        </div>
        <div class="space-y-1 text-xs md:text-sm">
            <?php 
            $rank = 1;
            while ($user = $leaderboard_query->fetch_assoc()): ?>
                <div class="flex justify-between">
                    <span class="truncate">#<?php echo $rank; ?> <?php echo htmlspecialchars(substr($user['username'], 0, 10)); ?></span>
                    <span class="leaderboard-score ml-2"><?php echo $user['score'] ?? 0; ?></span>
                </div>
            <?php 
                $rank++;
            endwhile; ?>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        class SoundManager {
            constructor() {
                this.audioContext = null;
                this.sounds = {};
                this.isEnabled = true;
                this.volume = 0.5;
                this.currentTimerSound = null;
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
                this.sounds.ambient = this.createTone(220, 'sine', 0.02, 2, true);
                this.sounds.tick = this.createTone(800, 'square', 0.1, 0.1);
                this.sounds.urgentTick = this.createTone(1200, 'sawtooth', 0.3, 0.2);
                this.sounds.hover = this.createTone(600, 'sine', 0.05, 0.1);
                this.sounds.click = this.createTone(400, 'triangle', 0.2, 0.15);
                this.sounds.wordReveal = this.createChord([330, 440, 550], 'sine', 0.15, 0.8);
                this.sounds.success = this.createChord([523, 659, 784], 'sine', 0.3, 1.2);
                this.sounds.failure = this.createTone(200, 'sawtooth', 0.4, 0.8);
                this.sounds.timeUp = this.createTone(150, 'square', 0.6, 1.5);
                this.sounds.dramatic = this.createDramaticSound();
                this.sounds.loseHeart = this.createTone(300, 'sawtooth', 0.3, 0.6);
                this.sounds.powerup = this.createChord([440, 554, 659], 'sine', 0.2, 0.8);
            }

            createTone(frequency, type, volume, duration, loop = false) {
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
                    if (loop) {
                        setTimeout(() => this.sounds.ambient(), duration * 1000);
                    }
                };
            }

            createChord(frequencies, type, volume, duration) {
                return () => {
                    if (!this.audioContext || !this.isEnabled) return;
                    frequencies.forEach((freq, index) => {
                        setTimeout(() => {
                            const oscillator = this.audioContext.createOscillator();
                            const gainNode = this.audioContext.createGain();
                            oscillator.type = type;
                            oscillator.frequency.setValueAtTime(freq, this.audioContext.currentTime);
                            gainNode.gain.setValueAtTime(0, this.audioContext.currentTime);
                            gainNode.gain.linearRampToValueAtTime(volume / frequencies.length, this.audioContext.currentTime + 0.01);
                            gainNode.gain.exponentialRampToValueAtTime(0.01, this.audioContext.currentTime + duration);
                            oscillator.connect(gainNode);
                            gainNode.connect(this.masterGain);
                            oscillator.start();
                            oscillator.stop(this.audioContext.currentTime + duration);
                        }, index * 100);
                    });
                };
            }

            createDramaticSound() {
                return () => {
                    if (!this.audioContext || !this.isEnabled) return;
                    const oscillator = this.audioContext.createOscillator();
                    const gainNode = this.audioContext.createGain();
                    oscillator.type = 'sawtooth';
                    oscillator.frequency.setValueAtTime(100, this.audioContext.currentTime);
                    oscillator.frequency.exponentialRampToValueAtTime(300, this.audioContext.currentTime + 0.5);
                    oscillator.frequency.exponentialRampToValueAtTime(80, this.audioContext.currentTime + 1);
                    gainNode.gain.setValueAtTime(0, this.audioContext.currentTime);
                    gainNode.gain.linearRampToValueAtTime(0.4, this.audioContext.currentTime + 0.1);
                    gainNode.gain.exponentialRampToValueAtTime(0.01, this.audioContext.currentTime + 1);
                    oscillator.connect(gainNode);
                    gainNode.connect(this.masterGain);
                    oscillator.start();
                    oscillator.stop(this.audioContext.currentTime + 1);
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

        let timeLeft = 10;
        const timerDisplay = document.getElementById('timer');
        let hasPlayedDramatic = false;

        const countdown = setInterval(() => {
            timeLeft--;
            timerDisplay.textContent = timeLeft;
            if (timeLeft <= 3 && timeLeft > 0) {
                timerDisplay.classList.add('urgent');
                soundManager.play('urgentTick');
                if (!hasPlayedDramatic) {
                    soundManager.play('dramatic');
                    hasPlayedDramatic = true;
                }
            } else if (timeLeft > 3) {
                soundManager.play('tick');
            }
            if (timeLeft <= 0) {
                clearInterval(countdown);
                soundManager.play('timeUp');
                const form = document.getElementById('answerForm');
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'answer';
                hiddenInput.value = '';
                form.appendChild(hiddenInput);
                form.submit();
            }
        }, 1000);

        setTimeout(() => {
            document.querySelectorAll('button[name="answer"]').forEach(button => {
                button.disabled = true;
            });
        }, 10000);

        const soundToggle = document.getElementById('soundToggle');
        const volumeControl = document.getElementById('volumeControl');

        soundToggle.addEventListener('click', () => {
            const isEnabled = soundManager.toggle();
            soundToggle.textContent = isEnabled ? '🔊' : '🔇';
            soundToggle.classList.toggle('muted', !isEnabled);
            if (isEnabled) {
                soundManager.resumeContext();
                soundManager.play('click');
            }
        });

        volumeControl.addEventListener('input', (e) => {
            const volume = e.target.value / 100;
            soundManager.setVolume(volume);
        });

        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                soundManager.play('wordReveal');
                soundManager.play('ambient');
            }, 500);
            document.querySelectorAll('.answer-button').forEach(button => {
                button.addEventListener('mouseenter', () => {
                    soundManager.play('hover');
                });
                button.addEventListener('click', (e) => {
                    soundManager.play('click');
                    const ripple = document.createElement('div');
                    ripple.className = 'absolute inset-0 bg-white opacity-30 rounded-2xl transform scale-0 pointer-events-none';
                    button.appendChild(ripple);
                    setTimeout(() => {
                        ripple.style.transform = 'scale(1)';
                        ripple.style.transition = 'transform 0.3s ease-out';
                    }, 10);
                    setTimeout(() => {
                        ripple.remove();
                    }, 300);
                });
            });

            document.querySelectorAll('.powerup-button').forEach(button => {
                button.addEventListener('mouseenter', () => {
                    if (!button.disabled) {
                        soundManager.play('hover');
                    }
                });
                button.addEventListener('click', (e) => {
                    if (!button.disabled) {
                        soundManager.play('powerup');
                    }
                });
            });

            <?php if (isset($_SESSION['play_sound'])): ?>
                soundManager.play('<?php echo $_SESSION['play_sound']; ?>');
                <?php unset($_SESSION['play_sound']); ?>
            <?php endif; ?>
        });

        document.addEventListener('click', () => {
            soundManager.resumeContext();
        }, { once: true });

        const leaderboardToggle = document.getElementById('leaderboardToggle');
        const leaderboardPanel = document.getElementById('leaderboardPanel');
        const closeLeaderboard = document.getElementById('closeLeaderboard');

        if (leaderboardToggle) {
            leaderboardToggle.addEventListener('click', function() {
                leaderboardPanel.classList.toggle('translate-y-full');
                soundManager.play('click');
            });
        }

        if (closeLeaderboard) {
            closeLeaderboard.addEventListener('click', function() {
                leaderboardPanel.classList.add('translate-y-full');
                soundManager.play('click');
            });
        }

        document.querySelectorAll('button[name="answer"]').forEach(button => {
            button.addEventListener('touchstart', function(e) {
                e.preventDefault();
                this.style.transform = 'scale(0.95)';
                soundManager.play('hover');
            });
            button.addEventListener('touchend', function(e) {
                e.preventDefault();
                this.style.transform = '';
                soundManager.play('click');
                this.click();
            });
        });

        document.querySelectorAll('.powerup-button').forEach(button => {
            button.addEventListener('touchstart', function(e) {
                if (!this.disabled) {
                    e.preventDefault();
                    this.style.transform = 'scale(0.95)';
                    soundManager.play('hover');
                }
            });
            button.addEventListener('touchend', function(e) {
                if (!this.disabled) {
                    e.preventDefault();
                    this.style.transform = '';
                    soundManager.play('powerup');
                    this.click();
                }
            });
        });

        let lastTouchEnd = 0;
        document.addEventListener('touchend', function (event) {
            const now = (new Date()).getTime();
            if (now - lastTouchEnd <= 300) {
                event.preventDefault();
            }
            lastTouchEnd = now;
        }, false);

        const decorativeElements = document.querySelectorAll('.floating-animation');
        decorativeElements.forEach((element, index) => {
            element.style.animationDelay = `${index * 0.5}s`;
        });

        window.addEventListener('orientationchange', function() {
            setTimeout(function() {
                window.scrollTo(0, 0);
            }, 100);
        });
    </script>
</body>
</html>