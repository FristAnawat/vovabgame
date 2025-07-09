<?php
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// ดึงข้อมูลผู้ใช้
$username = $_SESSION['username'] ?? 'ผู้เล่น';
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Oxford Game - หน้าหลัก</title>
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
                            'serif': ['Playfair Display', 'serif']
                        }
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --oxford-blue: #002147;
            --oxford-gold: #FFD700;
            --book-brown: #8B4513;
            --parchment: #F4E4BC;
        }

        .floating-animation {
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .gradient-text {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .level-card {
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }

        .level-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 33, 71, 0.4);
        }

        .level-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.5s ease;
        }

        .level-card:hover::before {
            left: 100%;
        }

        .pulse-icon {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .icon-bounce {
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }

        .difficulty-easy { background: linear-gradient(135deg, #10b981, #059669); }
        .difficulty-medium { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .difficulty-hard { background: linear-gradient(135deg, #dc2626, #b91c1c); }

        .stars {
            display: flex;
            justify-content: center;
            margin-bottom: 1rem;
        }

        .star {
            color: #FFD700;
            font-size: 1.5rem;
            margin: 0 2px;
            opacity: 0.3;
            transition: opacity 0.3s ease;
        }

        .star.active {
            opacity: 1;
            animation: twinkle 1.5s infinite;
        }

        @keyframes twinkle {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .touch-button {
            -webkit-tap-highlight-color: transparent;
            touch-action: manipulation;
        }

        .touch-button:active {
            transform: scale(0.95);
        }

        @media (max-width: 768px) {
            .mobile-title {
                font-size: 3rem;
            }
            
            .mobile-subtitle {
                font-size: 1.2rem;
            }
            
            .mobile-card {
                padding: 2rem;
            }
        }

        .welcome-section {
            background: rgba(0, 33, 71, 0.3);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 215, 0, 0.2);
        }

        .logout-btn {
            background: linear-gradient(135deg, #7c2d12, #991b1b);
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: linear-gradient(135deg, #991b1b, #b91c1c);
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-oxford-blue via-blue-900 to-indigo-900 relative overflow-x-hidden">
    <!-- Background Decorations -->
    <div class="absolute inset-0 opacity-10">
        <div class="absolute top-10 left-10 w-16 h-16 border-2 border-oxford-gold rounded-full floating-animation"></div>
        <div class="absolute top-32 right-16 w-12 h-12 border-2 border-oxford-gold transform rotate-45 floating-animation" style="animation-delay: 1s;"></div>
        <div class="absolute bottom-32 left-1/4 w-20 h-20 border-2 border-oxford-gold rounded-full floating-animation" style="animation-delay: 2s;"></div>
        <div class="absolute bottom-20 right-10 w-14 h-14 border-2 border-oxford-gold transform rotate-12 floating-animation" style="animation-delay: 0.5s;"></div>
    </div>

    <!-- Header -->
    <header class="relative z-10 p-6">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 bg-oxford-gold rounded-full flex items-center justify-center pulse-icon">
                    <span class="text-oxford-blue font-bold text-xl">🎓</span>
                </div>
                <div>
                    <h1 class="text-2xl md:text-3xl font-serif font-bold gradient-text">Oxford Game</h1>
                    <p class="text-sm text-gray-300">เกมทายคำศัพท์ภาษาอังกฤษ</p>
                </div>
            </div>
            <a href="logout.php" class="logout-btn text-white px-6 py-3 rounded-full font-semibold touch-button">
                ออกจากระบบ
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-6 py-8">
        <!-- Welcome Section -->
        <div class="welcome-section rounded-2xl p-8 mb-12 text-center">
            <div class="icon-bounce text-6xl mb-6">📚</div>
            <h2 class="mobile-title md:text-5xl font-serif font-bold gradient-text mb-4">
                ยินดีต้อนรับ, <?php echo htmlspecialchars($username); ?>!
            </h2>
            <p class="mobile-subtitle md:text-xl text-gray-200 mb-8">
                เลือกระดับความยากที่คุณต้องการเล่น
            </p>
        </div>

        <!-- Level Selection -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto">
            <!-- ระดับง่าย -->
            <div class="level-card difficulty-easy rounded-3xl p-8 mobile-card text-white text-center touch-button">
                <div class="stars">
                    <span class="star active">★</span>
                </div>
                <div class="text-6xl mb-6 pulse-icon">📗</div>
                <h3 class="text-3xl font-bold mb-4">ระดับง่าย</h3>
                <p class="text-green-100 mb-6 text-lg">
                    คำศัพท์พื้นฐาน<br>
                    เหมาะสำหรับผู้เริ่มต้น
                </p>
                <div class="mb-6">
                    <div class="text-sm text-green-200">ตัวช่วยข้าม1ครั้งเเละคะแนนx2</div>
                    <div class="text-sm text-green-200">เวลา: 10 วินาที/คำถาม</div>
                </div>
                <a href="index.php?level=easy" class="bg-white text-green-600 px-8 py-4 rounded-xl font-bold text-lg hover:bg-green-50 transition-all inline-block w-full">
                    เริ่มเล่น
                </a>
            </div>

            <!-- ระดับปานกลาง -->
            <div class="level-card difficulty-medium rounded-3xl p-8 mobile-card text-white text-center touch-button">
                <div class="stars">
                    <span class="star active">★</span>
                    <span class="star active">★</span>
                </div>
                <div class="text-6xl mb-6 pulse-icon">📒</div>
                <h3 class="text-3xl font-bold mb-4">ระดับปานกลาง</h3>
                <p class="text-yellow-100 mb-6 text-lg">
                    คำศัพท์ระดับกลาง<br>
                    เหมาะสำหรับผู้มีพื้นฐาน
                </p>
                <div class="mb-6">
                    <div class="text-sm text-yellow-200">ตัวช่วยข้าม1ครั้งเเละคะแนนx2</div>
                    <div class="text-sm text-yellow-200">เวลา: 10 วินาที/คำถาม</div>
                </div>
                <a href="index.php?level=medium" class="bg-white text-yellow-600 px-8 py-4 rounded-xl font-bold text-lg hover:bg-yellow-50 transition-all inline-block w-full">
                    เริ่มเล่น
                </a>
            </div>

            <!-- ระดับยาก -->
            <div class="level-card difficulty-hard rounded-3xl p-8 mobile-card text-white text-center touch-button">
                <div class="stars">
                    <span class="star active">★</span>
                    <span class="star active">★</span>
                    <span class="star active">★</span>
                </div>
                <div class="text-6xl mb-6 pulse-icon">📙</div>
                <h3 class="text-3xl font-bold mb-4">ระดับยาก</h3>
                <p class="text-red-100 mb-6 text-lg">
                    คำศัพท์ขั้นสูง<br>
                    เหมาะสำหรับผู้เชี่ยวชาญ
                </p>
                <div class="mb-6">
                    <div class="text-sm text-red-200">ตัวช่วยข้าม1ครั้งเเละคะแนนx2</div>
                    <div class="text-sm text-red-200">เวลา: 10 วินาที/คำถาม</div>
                </div>
                <a href="index.php?level=hard" class="bg-white text-red-600 px-8 py-4 rounded-xl font-bold text-lg hover:bg-red-50 transition-all inline-block w-full">
                    เริ่มเล่น
                </a>
            </div>
        </div>

        <!-- Decorative Elements -->
        <div class="flex justify-center space-x-4 mt-16 opacity-30">
            <div class="w-2 h-2 bg-oxford-gold rounded-full animate-ping"></div>
            <div class="w-2 h-2 bg-oxford-gold rounded-full animate-ping" style="animation-delay: 0.5s;"></div>
            <div class="w-2 h-2 bg-oxford-gold rounded-full animate-ping" style="animation-delay: 1s;"></div>
        </div>
    </main>

    <!-- JavaScript -->
    <script>
        // Audio context and sound effects
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        
        // Function to load and play sound
        function playSound(url, volume = 1.0) {
            fetch(url)
                .then(response => response.arrayBuffer())
                .then(data => audioContext.decodeAudioData(data))
                .then(buffer => {
                    const source = audioContext.createBufferSource();
                    const gainNode = audioContext.createGain();
                    source.buffer = buffer;
                    gainNode.gain.value = volume;
                    source.connect(gainNode);
                    gainNode.connect(audioContext.destination);
                    source.start();
                })
                .catch(err => console.error('Error playing sound:', err));
        }

        // Initialize sounds
        const sounds = {
            click: 'sounds/click.mp3',
            hover: 'sounds/hover.mp3',
            welcome: 'sounds/welcome.mp3'
        };

        // Play welcome sound on page load
        document.addEventListener('DOMContentLoaded', () => {
            // Resume audio context on user interaction (required for some browsers)
            const resumeAudio = () => {
                if (audioContext.state === 'suspended') {
                    audioContext.resume().then(() => {
                        playSound(sounds.welcome, 0.5);
                    });
                } else {
                    playSound(sounds.welcome, 0.5);
                }
                document.removeEventListener('click', resumeAudio);
            };
            document.addEventListener('click', resumeAudio);
        });

        // Level card hover and click effects
        document.querySelectorAll('.level-card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-10px) scale(1.02)';
                playSound(sounds.hover, 0.3);
                const stars = card.querySelectorAll('.star');
                stars.forEach((star, index) => {
                    setTimeout(() => {
                        star.classList.add('active');
                    }, index * 100);
                });
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0) scale(1)';
            });
        });

        // Button click effects
        document.querySelectorAll('.touch-button').forEach(button => {
            button.addEventListener('click', function() {
                playSound(sounds.click, 0.4);
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
            });
        });

        // Logout button specific sound
        document.querySelector('.logout-btn').addEventListener('click', () => {
            playSound(sounds.click, 0.4);
        });
    </script>
</body>
</html>