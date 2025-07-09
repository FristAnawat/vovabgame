<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $query = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $query->bind_param("s", $username);
    $query->execute();
    $result = $query->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        header("Location: home.php");
        exit();
    } else {
        $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
        $_SESSION['play_sound'] = 'error'; // ตั้งค่าให้เล่นเสียงเมื่อเกิดข้อผิดพลาด
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - เกมทายศัพท์</title>
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
                        'thai': ['Sarabun', 'sans-serif']
                    },
                    animation: {
                        'bounce-slow': 'bounce 2s infinite',
                        'pulse-slow': 'pulse 3s infinite',
                        'float': 'float 3s ease-in-out infinite',
                        'wiggle': 'wiggle 1s ease-in-out infinite',
                        'glow': 'glow 2s ease-in-out infinite alternate',
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-10px)' },
                        },
                        wiggle: {
                            '0%, 100%': { transform: 'rotate(-3deg)' },
                            '50%': { transform: 'rotate(3deg)' },
                        },
                        glow: {
                            '0%': { boxShadow: '0 0 20px rgba(168, 85, 247, 0.4)' },
                            '100%': { boxShadow: '0 0 40px rgba(168, 85, 247, 0.8)' },
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
        .letter-tile-active {
            background: linear-gradient(145deg, #8b5cf6, #7c3aed);
            color: white;
            box-shadow: 4px 4px 12px rgba(139, 92, 246, 0.5), -4px -4px 12px rgba(196, 181, 253, 0.3);
        }
        .floating-letters {
            position: absolute;
            font-size: 2rem;
            color: rgba(99, 102, 241, 0.1);
            animation: float 4s ease-in-out infinite;
            pointer-events: none;
        }
        .word-scramble {
            animation: wiggle 0.5s ease-in-out;
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
        .touch-button {
            -webkit-tap-highlight-color: transparent;
            touch-action: manipulation;
        }
        .touch-button:active {
            transform: scale(0.95);
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 font-thai relative overflow-hidden">
    <!-- Sound Control Button -->
    <div class="sound-control" id="soundToggle" title="เปิด/ปิดเสียง">🔊</div>

    <!-- Volume Slider -->
    <div class="volume-slider" id="volumeSlider">
        <input type="range" min="0" max="100" value="50" id="volumeControl">
    </div>

    <!-- Floating Background Letters -->
    <div class="floating-letters" style="top: 15%; left: 8%; animation-delay: 0s; font-size: 3rem;">W</div>
    <div class="floating-letters" style="top: 25%; right: 12%; animation-delay: 1s; font-size: 2.5rem;">O</div>
    <div class="floating-letters" style="top: 65%; left: 3%; animation-delay: 2s; font-size: 3.5rem;">R</div>
    <div class="floating-letters" style="top: 75%; right: 8%; animation-delay: 3s; font-size: 2.8rem;">D</div>
    <div class="floating-letters" style="top: 45%; left: 88%; animation-delay: 1.5s; font-size: 2.2rem;">S</div>
    <div class="floating-letters" style="top: 85%; left: 78%; animation-delay: 2.5s; font-size: 3rem;">!</div>
    
    <!-- Thai letters -->
    <div class="floating-letters" style="top: 35%; left: 15%; animation-delay: 0.5s; font-size: 2.5rem;">ก</div>
    <div class="floating-letters" style="top: 55%; right: 20%; animation-delay: 2.2s; font-size: 2.8rem;">ม</div>
    
    <div class="flex items-center justify-center min-h-screen p-4 relative z-10">
        <div class="w-full max-w-md">
            <!-- Welcome Back Section -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center mb-6">
                    <!-- Animated Word Tiles -->
                    <div class="flex space-x-2">
                        <div class="letter-tile-active w-12 h-12 rounded-xl flex items-center justify-center font-bold text-lg animate-bounce" style="animation-delay: 0s;">O</div>
                        <div class="letter-tile-active w-12 h-12 rounded-xl flex items-center justify-center font-bold text-lg animate-bounce" style="animation-delay: 0.1s;">X</div>
                        <div class="letter-tile-active w-12 h-12 rounded-xl flex items-center justify-center font-bold text-lg animate-bounce" style="animation-delay: 0.2s;">F</div>
                        <div class="letter-tile-active w-12 h-12 rounded-xl flex items-center justify-center font-bold text-lg animate-bounce" style="animation-delay: 0.3s;">O</div>
                        <div class="letter-tile-active w-12 h-12 rounded-xl flex items-center justify-center font-bold text-lg animate-bounce" style="animation-delay: 0.4s;">R</div>
                        <div class="letter-tile-active w-12 h-12 rounded-xl flex items-center justify-center font-bold text-lg animate-bounce" style="animation-delay: 0.5s;">D</div>
                    </div>
                </div>
                <h1 class="text-4xl font-bold text-white mb-2 drop-shadow-lg">ยินดีต้อนรับกลับ!</h1>
                <p class="text-purple-100 text-lg">เข้าสู่โลกแห่งการทายศัพท์</p>
            </div>

            <!-- Login Form -->
            <div class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-2xl p-8 border border-white/20 animate-glow">
                <div class="text-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-3">เข้าสู่ระบบ</h2>
                    <!-- Word Grid Decoration -->
                    <div class="grid grid-cols-5 gap-1 max-w-40 mx-auto mb-4">
                        <div class="letter-tile w-6 h-6 rounded flex items-center justify-center text-xs font-bold text-purple-600">L</div>
                        <div class="letter-tile w-6 h-6 rounded flex items-center justify-center text-xs font-bold text-pink-600">O</div>
                        <div class="letter-tile w-6 h-6 rounded flex items-center justify-center text-xs font-bold text-indigo-600">G</div>
                        <div class="letter-tile w-6 h-6 rounded flex items-center justify-center text-xs font-bold text-purple-600">I</div>
                        <div class="letter-tile w-6 h-6 rounded flex items-center justify-center text-pink-600">N</div>
                        <div class="w-6 h-6"></div>
                    </div>
                </div>

                <form method="POST" action="" class="space-y-6">
                    <?php if (!empty($error)): ?>
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg animate-pulse">
                            <div class="flex items-center">
                                <span class="text-2xl mr-3 animate-wiggle">❌</span>
                                <div>
                                    <p class="font-semibold">เข้าสู่ระบบไม่สำเร็จ!</p>
                                    <p class="text-sm"><?php echo htmlspecialchars($error); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="space-y-5">
                        <div class="group">
                            <label for="username" class="block text-sm font-semibold text-gray-700 mb-2">
                                👤 ชื่อผู้ใช้
                            </label>
                            <input type="text" 
                                   class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:ring-0 transition-all duration-300 bg-gray-50 focus:bg-white focus:shadow-lg group-hover:border-purple-300" 
                                   id="username" 
                                   name="username" 
                                   placeholder="กรอกชื่อผู้ใช้ของคุณ"
                                   required>
                        </div>

                        <div class="group">
                            <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                                🔐 รหัสผ่าน
                            </label>
                            <input type="password" 
                                   class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:ring-0 transition-all duration-300 bg-gray-50 focus:bg-white focus:shadow-lg group-hover:border-purple-300" 
                                   id="password" 
                                   name="password" 
                                   placeholder="กรอกรหัสผ่านของคุณ"
                                   required>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <button type="submit" 
                                class="w-full bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 text-white font-bold py-3 px-6 rounded-xl hover:from-indigo-700 hover:via-purple-700 hover:to-pink-700 transform hover:scale-105 transition-all duration-300 shadow-lg hover:shadow-xl relative overflow-hidden group touch-button">
                            <span class="relative z-10 flex items-center justify-center">
                                🎮 เข้าสู่ระบบ
                            </span>
                            <div class="absolute inset-0 bg-gradient-to-r from-purple-600 to-pink-600 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        </button>

                        <div class="text-center">
                            <span class="text-gray-600">ยังไม่มีบัญชี? </span>
                            <a href="register.php" 
                               class="text-purple-600 hover:text-purple-800 font-semibold hover:underline transition-all duration-300 inline-flex items-center touch-button">
                                สมัครสมาชิก
                                <span class="ml-1 transform group-hover:translate-x-1 transition-transform">→</span>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Decorative Elements -->
    <div class="absolute bottom-0 left-0 w-full h-32 bg-gradient-to-t from-black/10 to-transparent pointer-events-none"></div>
    
    <script>
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
                this.sounds.ambient = this.createTone(220, 'sine', 0.02, 2, true);
                this.sounds.click = this.createTone(400, 'triangle', 0.2, 0.15);
                this.sounds.hover = this.createTone(600, 'sine', 0.05, 0.1);
                this.sounds.type = this.createTone(800, 'square', 0.1, 0.05);
                this.sounds.error = this.createTone(200, 'sawtooth', 0.4, 0.8);
                this.sounds.wiggle = this.createTone(500, 'sine', 0.1, 0.2);
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

        document.addEventListener('DOMContentLoaded', function() {
            // Play ambient sound on page load
            setTimeout(() => {
                soundManager.play('ambient');
            }, 500);

            // Input focus and typing animations
            const inputs = document.querySelectorAll('input');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('animate-pulse-slow');
                });
                input.addEventListener('blur', function() {
                    this.parentElement.classList.remove('animate-pulse-slow');
                });
                input.addEventListener('keydown', () => {
                    soundManager.play('type');
                });
            });

            // Random letter tile animations with sound
            const letterTiles = document.querySelectorAll('.letter-tile, .letter-tile-active');
            setInterval(() => {
                const randomTile = letterTiles[Math.floor(Math.random() * letterTiles.length)];
                randomTile.classList.add('word-scramble');
                soundManager.play('wiggle');
                setTimeout(() => {
                    randomTile.classList.remove('word-scramble');
                }, 500);
            }, 3000);

            // Form submission animation and sound
            const form = document.querySelector('form');
            form.addEventListener('submit', function() {
                const button = this.querySelector('button[type="submit"]');
                button.innerHTML = '<span class="animate-spin inline-block">⏳</span> กำลังเข้าสู่ระบบ...';
                button.disabled = true;
                soundManager.play('click');
            });

            // Button and link interactions
            const buttons = document.querySelectorAll('.touch-button');
            buttons.forEach(button => {
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
                    if (button.tagName === 'A') {
                        window.location.href = button.href;
                    } else {
                        button.click();
                    }
                });
            });

            // Play error sound if login fails
            <?php if (isset($_SESSION['play_sound']) && $_SESSION['play_sound'] === 'error'): ?>
                soundManager.play('error');
                <?php unset($_SESSION['play_sound']); ?>
            <?php endif; ?>

            // Prevent double-tap zoom
            let lastTouchEnd = 0;
            document.addEventListener('touchend', (event) => {
                const now = new Date().getTime();
                if (now - lastTouchEnd <= 300) {
                    event.preventDefault();
                }
                lastTouchEnd = now;
            }, false);

            // Resume audio context on first interaction
            document.addEventListener('click', () => {
                soundManager.resumeContext();
            }, { once: true });
        });
    </script>
</body>
</html>