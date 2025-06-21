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
        header("Location: index.php");
        exit();
    } else {
        $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
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
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 font-thai relative overflow-hidden">
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
                        <div class="letter-tile-active w-12 h-12 rounded-xl flex items-center justify-center font-bold text-lg animate-bounce" style="animation-delay: 0.3s;">R</div>
                        <div class="letter-tile-active w-12 h-12 rounded-xl flex items-center justify-center font-bold text-lg animate-bounce" style="animation-delay: 0.3s;">D</div>
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
                                class="w-full bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 text-white font-bold py-3 px-6 rounded-xl hover:from-indigo-700 hover:via-purple-700 hover:to-pink-700 transform hover:scale-105 transition-all duration-300 shadow-lg hover:shadow-xl relative overflow-hidden group">
                            <span class="relative z-10 flex items-center justify-center">
                                🎮 เข้าสู่ระบบ
                            </span>
                            <div class="absolute inset-0 bg-gradient-to-r from-purple-600 to-pink-600 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        </button>

                        <div class="text-center">
                            <span class="text-gray-600">ยังไม่มีบัญชี? </span>
                            <a href="register.php" 
                               class="text-purple-600 hover:text-purple-800 font-semibold hover:underline transition-all duration-300 inline-flex items-center">
                                สมัครสมาชิก
                                <span class="ml-1 transform group-hover:translate-x-1 transition-transform">→</span>
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Game Stats Preview -->
            <div class="mt-6 grid grid-cols-3 gap-3">
                <div class="bg-white/20 backdrop-blur-sm rounded-xl p-3 text-center border border-white/30">
                    <div class="text-2xl mb-1">🏆</div>
                    <div class="text-white text-xs font-semibold">คะแนนสูงสุด</div>
                </div>
                <div class="bg-white/20 backdrop-blur-sm rounded-xl p-3 text-center border border-white/30">
                    <div class="text-2xl mb-1">⚡</div>
                    <div class="text-white text-xs font-semibold">เล่นด่วน</div>
                </div>
                <div class="bg-white/20 backdrop-blur-sm rounded-xl p-3 text-center border border-white/30">
                    <div class="text-2xl mb-1">🎯</div>
                    <div class="text-white text-xs font-semibold">ความแม่นยำ</div>
                </div>
            </div>

            <!-- Daily Challenge Teaser -->
            <div class="text-center mt-6">
                <div class="bg-gradient-to-r from-yellow-400 to-orange-500 text-white rounded-xl p-4 shadow-lg transform hover:scale-105 transition-all duration-300">
                    <div class="flex items-center justify-center space-x-2">
                        <span class="text-2xl animate-bounce">🌟</span>
                        <span class="font-bold">ท้าทายประจำวัน</span>
                        <span class="text-2xl animate-bounce" style="animation-delay: 0.5s;">🌟</span>
                    </div>
                    <p class="text-sm mt-1 opacity-90">เข้าสู่ระบบเพื่อรับโบนัสพิเศษ!</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Decorative Elements -->
    <div class="absolute bottom-0 left-0 w-full h-32 bg-gradient-to-t from-black/10 to-transparent pointer-events-none"></div>
    
    <script>
        // Interactive animations
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input');
            const letterTiles = document.querySelectorAll('.letter-tile, .letter-tile-active');
            
            // Input focus animations
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('animate-pulse-slow');
                });
                input.addEventListener('blur', function() {
                    this.parentElement.classList.remove('animate-pulse-slow');
                });
            });

            // Random letter tile animations
            setInterval(() => {
                const randomTile = letterTiles[Math.floor(Math.random() * letterTiles.length)];
                randomTile.classList.add('word-scramble');
                setTimeout(() => {
                    randomTile.classList.remove('word-scramble');
                }, 500);
            }, 3000);

            // Form submission animation
            const form = document.querySelector('form');
            form.addEventListener('submit', function() {
                const button = this.querySelector('button[type="submit"]');
                button.innerHTML = '<span class="animate-spin inline-block">⏳</span> กำลังเข้าสู่ระบบ...';
                button.disabled = true;
            });
        });
    </script>
</body>
</html>