<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// กำหนดค่าเริ่มต้นให้คะแนนถ้ายังไม่มี
if (!isset($_SESSION['score'])) {
    $_SESSION['score'] = 0;
}

include 'db.php';

// ดึงคะแนนสูงสุดของผู้ใช้จากตาราง scores
$user_id = $_SESSION['user_id'];
$score_query = $conn->prepare("SELECT MAX(score) as high_score FROM scores WHERE user_id = ?");
$score_query->bind_param("i", $user_id);
$score_query->execute();
$score_result = $score_query->get_result();
$high_score = $score_result->fetch_assoc()['high_score'] ?? 0;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Oxford Game</title>
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
        
        /* Mobile specific styles */
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
        
        /* Touch-friendly interactions */
        .touch-button {
            -webkit-tap-highlight-color: transparent;
            touch-action: manipulation;
        }
        
        .touch-button:active {
            transform: scale(0.95);
        }
        
        /* Timer styles */
        .timer {
            font-size: 1.5rem;
            font-weight: bold;
            color: #FFD700;
            text-shadow: 0 0 10px rgba(255,215,0,0.5);
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-oxford-blue via-blue-900 to-indigo-900 relative overflow-x-hidden">
    
    <!-- Background Pattern - Reduced for mobile -->
    <div class="absolute inset-0 opacity-5 md:opacity-10">
        <div class="absolute top-5 left-5 w-12 h-12 md:w-20 md:h-20 border-2 border-oxford-gold rounded-full"></div>
        <div class="absolute top-20 right-10 w-8 h-8 md:w-16 md:h-16 border-2 border-oxford-gold transform rotate-45"></div>
        <div class="absolute bottom-20 left-1/4 w-6 h-6 md:w-12 md:h-12 border-2 border-oxford-gold rounded-full"></div>
        <div class="absolute bottom-40 right-5 w-10 h-10 md:w-24 md:h-24 border-2 border-oxford-gold transform rotate-12"></div>
    </div>

    <!-- Top Bar with Score, Timer, and Logout -->
    <div class="fixed top-0 left-0 right-0 z-50">
        <div class="flex justify-between items-center p-3 md:p-4">
            <!-- Score Display -->
            <div class="bg-oxford-gold text-oxford-blue px-3 py-2 md:px-4 md:py-3 rounded-full font-bold text-sm md:text-base shadow-lg">
                <div class="text-xs md:text-sm">คะแนนปัจจุบัน: <?php echo $_SESSION['score']; ?></div>
                <div class="text-xs md:text-sm">คะแนนสูงสุด: <?php echo $high_score; ?></div>
            </div>
            
            <!-- Timer Display -->
            <div class="timer" id="timer">10</div>
            
            <!-- Logout Button -->
            <a href="logout.php" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 md:px-6 md:py-3 rounded-full font-semibold text-sm md:text-base transition-all duration-300 shadow-lg touch-button">
                ออกจากระบบ
            </a>
        </div>
    </div>

    <!-- Main Container -->
    <div class="container mx-auto mobile-padding pt-20 pb-6 text-center min-h-screen flex flex-col justify-center">
        
        <!-- Header Section -->
        <div class="mb-8 md:mb-12 floating-animation">
            <h1 class="mobile-title md:text-6xl lg:text-7xl font-serif font-bold gradient-text mb-2 md:mb-4">
                Oxford Game
            </h1>
            <p class="text-sm md:text-xl text-gray-200 font-light px-4">
                ทายคำศัพท์ให้ถูกต้อง เพิ่มพูนความรู้ภาษาอังกฤษ
            </p>
        </div>

        <?php
        $query = $conn->query("SELECT * FROM words ORDER BY RAND() LIMIT 1");
        $word = $query->fetch_assoc();

        // สุ่มตำแหน่งคำแปล
        $translations = [
            ['value' => $word['correct_translation'], 'is_correct' => true],
            ['value' => $word['wrong_translation'], 'is_correct' => false]
        ];

        shuffle($translations);  // สุ่มลำดับของคำแปล
        ?>

        <!-- Word Display -->
        <div class="mb-8 md:mb-12">
            <div class="bg-gradient-to-r from-parchment to-yellow-100 text-oxford-blue p-4 md:p-8 rounded-2xl book-shadow mx-auto border-2 md:border-4 border-oxford-gold">
                <div class="bg-white bg-opacity-50 rounded-xl p-4 md:p-6 backdrop-blur-sm">
                    <h2 class="mobile-word md:text-5xl lg:text-6xl font-serif font-bold mb-1 md:mb-2 break-words">
                        <?php echo $word['word']; ?>
                    </h2>
                    <div class="w-12 md:w-24 h-1 bg-oxford-blue mx-auto"></div>
                </div>
            </div>
        </div>

        <!-- Answer Choices -->
        <form id="answerForm" method="POST" action="check_answer.php" class="mb-8 md:mb-12">
            <input type="hidden" name="word_id" value="<?php echo $word['id']; ?>">
            
            <div class="flex flex-col gap-4 md:gap-6 justify-center items-center max-w-md md:max-w-4xl mx-auto">
                <?php foreach ($translations as $index => $translation): ?>
                    <button 
                        type="submit" 
                        name="answer" 
                        value="<?php echo htmlspecialchars($translation['value']); ?>" 
                        class="group relative w-full mobile-button bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 active:from-emerald-700 active:to-teal-700 text-white px-6 md:px-8 py-4 md:py-6 rounded-2xl font-semibold text-lg md:text-xl transition-all duration-200 shadow-lg hover:shadow-2xl transform hover:scale-105 book-shadow touch-button"
                    >
                        <div class="absolute inset-0 bg-gradient-to-r from-oxford-gold to-yellow-400 rounded-2xl opacity-0 group-hover:opacity-20 group-active:opacity-30 transition-opacity duration-200"></div>
                        <span class="relative z-10 break-words"><?php echo htmlspecialchars($translation['value']); ?></span>
                        <div class="absolute -top-1 -right-1 md:-top-2 md:-right-2 w-4 h-4 md:w-6 md:h-6 bg-oxford-gold rounded-full opacity-0 group-hover:opacity-100 transition-all duration-200 transform scale-0 group-hover:scale-100"></div>
                    </button>
                <?php endforeach; ?>
            </div>
        </form>

        <!-- Decorative Elements -->
        <div class="flex justify-center space-x-4 opacity-30">
            <div class="w-1.5 h-1.5 md:w-2 md:h-2 bg-oxford-gold rounded-full animate-ping"></div>
            <div class="w-1.5 h-1.5 md:w-2 md:h-2 bg-oxford-gold rounded-full animate-ping" style="animation-delay: 0.5s;"></div>
            <div class="w-1.5 h-1.5 md:w-2 md:h-2 bg-oxford-gold rounded-full animate-ping" style="animation-delay: 1s;"></div>
        </div>

    </div>

    <!-- Leaderboard Section - Mobile Optimized -->
    <?php
    // ดึงคะแนนจากตาราง scores แทน users
    $leaderboardQuery = $conn->query("
        SELECT u.username, MAX(s.score) as score
        FROM scores s
        JOIN users u ON s.user_id = u.id
        GROUP BY u.id, u.username
        ORDER BY score DESC LIMIT 5
    ");
    ?>
    
    <!-- Leaderboard Toggle Button for Mobile -->
    <button id="leaderboardToggle" class="fixed bottom-4 right-4 md:hidden bg-oxford-gold text-oxford-blue p-3 rounded-full shadow-lg touch-button z-40">
        🏆
    </button>
    
    <!-- Leaderboard Panel -->
    <div id="leaderboardPanel" class="fixed bottom-4 right-4 bg-black bg-opacity-90 backdrop-blur-sm rounded-xl p-4 text-white max-w-xs transform translate-y-full md:translate-y-0 transition-transform duration-300 z-30">
        <div class="flex justify-between items-center mb-2 md:mb-2">
            <h3 class="text-base md:text-lg font-semibold text-oxford-gold">🏆 อันดับคะแนน</h3>
            <button id="closeLeaderboard" class="md:hidden text-gray-400 hover:text-white">✕</button>
        </div>
        <div class="space-y-1 text-xs md:text-sm">
            <?php 
            $rank = 1;
            while ($user = $leaderboardQuery->fetch_assoc()): 
            ?>
                <div class="flex justify-between">
                    <span class="truncate">#<?php echo $rank; ?> <?php echo htmlspecialchars(substr($user['username'], 0, 10)); ?></span>
                    <span class="text-oxford-gold ml-2"><?php echo $user['score'] ?? 0; ?></span>
                </div>
            <?php 
                $rank++;
            endwhile; 
            ?>
        </div>
    </div>

    <!-- JavaScript for Enhanced Mobile Interactions and Timer -->
    <script>
        // Timer functionality
        let timeLeft = 10;
        const timerDisplay = document.getElementById('timer');
        
        const countdown = setInterval(() => {
            timeLeft--;
            timerDisplay.textContent = timeLeft;
            
            if (timeLeft <= 0) {
                clearInterval(countdown);
                // Submit the form with a default or empty answer to check_answer.php
                const form = document.getElementById('answerForm');
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'answer';
                hiddenInput.value = '';
                form.appendChild(hiddenInput);
                form.submit();
            }
        }, 1000);

        // Disable answer buttons when time is up
        setTimeout(() => {
            document.querySelectorAll('button[name="answer"]').forEach(button => {
                button.disabled = true;
            });
        }, 10000); // Changed to 10000ms (10 seconds) to match timer

        // Mobile leaderboard toggle
        const leaderboardToggle = document.getElementById('leaderboardToggle');
        const leaderboardPanel = document.getElementById('leaderboardPanel');
        const closeLeaderboard = document.getElementById('closeLeaderboard');
        
        if (leaderboardToggle) {
            leaderboardToggle.addEventListener('click', function() {
                leaderboardPanel.classList.toggle('translate-y-full');
            });
        }
        
        if (closeLeaderboard) {
            closeLeaderboard.addEventListener('click', function() {
                leaderboardPanel.classList.add('translate-y-full');
            });
        }

        // Enhanced click animation for mobile
        document.querySelectorAll('button[name="answer"]').forEach(button => {
            button.addEventListener('touchstart', function(e) {
                e.preventDefault(); // Prevent default to ensure consistent behavior
                this.style.transform = 'scale(0.95)';
            });
            
            button.addEventListener('touchend', function(e) {
                e.preventDefault(); // Prevent default to ensure consistent behavior
                this.style.transform = '';
                this.click(); // Trigger click event programmatically
            });
            
            button.addEventListener('click', function(e) {
                // Create ripple effect
                const ripple = document.createElement('div');
                ripple.className = 'absolute inset-0 bg-white opacity-30 rounded-2xl transform scale-0 pointer-events-none';
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

        // Prevent zoom on double tap
        let lastTouchEnd = 0;
        document.addEventListener('touchend', function (event) {
            const now = (new Date()).getTime();
            if (now - lastTouchEnd <= 300) {
                event.preventDefault();
            }
            lastTouchEnd = now;
        }, false);

        // Add floating animation to decorative elements
        const decorativeElements = document.querySelectorAll('.floating-animation');
        decorativeElements.forEach((element, index) => {
            element.style.animationDelay = `${index * 0.5}s`;
        });

        // Handle orientation change
        window.addEventListener('orientationchange', function() {
            setTimeout(function() {
                window.scrollTo(0, 0);
            }, 100);
        });
    </script>

</body>
</html>