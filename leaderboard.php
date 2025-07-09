<?php
session_start();
include 'db.php';

// ตรวจสอบสิทธิ์การเข้าถึง
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);
$current_score = $_SESSION['score'] ?? 0;

// ดึงข้อมูลผู้เล่นปัจจุบัน
$user_query = $conn->prepare("SELECT username FROM users WHERE id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user_result = $user_query->get_result();
$user_data = $user_result->fetch_assoc();
$username = $user_data['username'] ?? 'Unknown';

// ดึงคะแนนสูงสุดของผู้เล่นปัจจุบัน
$player_high_score_query = $conn->prepare("
    SELECT MAX(score) as high_score, 
           COUNT(*) as total_games,
           AVG(score) as avg_score
    FROM scores 
    WHERE user_id = ?
");
$player_high_score_query->bind_param("i", $user_id);
$player_high_score_query->execute();
$player_stats = $player_high_score_query->get_result()->fetch_assoc();

$player_high_score = $player_stats['high_score'] ?? 0;
$total_games = $player_stats['total_games'] ?? 0;
$avg_score = round($player_stats['avg_score'] ?? 0, 2);

// ดึงอันดับปัจจุบันของผู้เล่น
$rank_query = $conn->prepare("
    SELECT COUNT(*) + 1 as player_rank 
    FROM (
        SELECT user_id, MAX(score) as max_score 
        FROM scores 
        GROUP BY user_id 
        HAVING max_score > ?
    ) as higher_scores
");
$rank_query->bind_param("i", $player_high_score);
$rank_query->execute();
$rank_result = $rank_query->get_result();
$player_rank = $rank_result->fetch_assoc()['player_rank'] ?? 'N/A';

// ดึงข้อมูลอันดับ Top 5
$top5_query = $conn->prepare("
    SELECT u.username, MAX(s.score) as high_score, COUNT(s.id) as total_games
    FROM scores s 
    JOIN users u ON s.user_id = u.id 
    GROUP BY s.user_id, u.username 
    ORDER BY high_score DESC 
    LIMIT 5
");
$top5_query->execute();
$top5_result = $top5_query->get_result();
$top5_players = [];
while ($row = $top5_result->fetch_assoc()) {
    $top5_players[] = $row;
}

// ดึงสถิติเกมทั้งหมด
$total_stats_query = $conn->prepare("
    SELECT COUNT(*) as total_games_played, 
           AVG(score) as overall_avg_score,
           MAX(score) as highest_score_ever
    FROM scores
");
$total_stats_query->execute();
$total_stats = $total_stats_query->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oxford Game - คะแนนและอันดับ</title>
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
            box-shadow: 0 10px 20px rgba(0, 33, 71, 0.3);
        }
        .gold-gradient {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .rank-badge {
            position: relative;
            overflow: hidden;
        }
        .rank-badge::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        .rank-badge:hover::before {
            left: 100%;
        }
        .trophy-animation {
            animation: bounce 2s infinite;
        }
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }
        .stat-card {
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 33, 71, 0.4);
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-oxford-blue via-blue-900 to-indigo-900 text-white">
    
    <!-- Header -->
    <div class="container mx-auto px-4 py-6">
        <div class="text-center mb-8">
            <h1 class="text-4xl md:text-6xl font-serif font-bold gold-gradient mb-4">
                📊 คะแนนและอันดับ
            </h1>
            <div class="w-32 h-1 bg-oxford-gold mx-auto"></div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 pb-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Player Stats Section -->
            <div class="lg:col-span-1">
                <div class="bg-gradient-to-br from-oxford-blue to-blue-800 p-6 rounded-2xl book-shadow stat-card">
                    <div class="text-center mb-6">
                        <div class="w-20 h-20 bg-oxford-gold rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="text-2xl">👤</span>
                        </div>
                        <h2 class="text-2xl font-bold gold-gradient"><?php echo htmlspecialchars($username); ?></h2>
                        <p class="text-gray-300">นักเรียนภาษาอังกฤษ</p>
                    </div>
                    
                    <!-- Current Session -->
                    <div class="bg-green-600 bg-opacity-20 p-4 rounded-xl mb-4 border border-green-400">
                        <h3 class="text-lg font-semibold text-green-300 mb-2">🎮 เกมปัจจุบัน</h3>
                        <p class="text-2xl font-bold text-green-200">
                            <?php echo $current_score; ?> คะแนน
                        </p>
                    </div>
                    
                    <!-- Personal Records -->
                    <div class="space-y-3">
                        <div class="flex justify-between items-center bg-yellow-600 bg-opacity-20 p-3 rounded-lg">
                            <span class="text-yellow-300">🏆 คะแนนสูงสุด</span>
                            <span class="font-bold text-yellow-200"><?php echo $player_high_score; ?></span>
                        </div>
                        
                        <div class="flex justify-between items-center bg-blue-600 bg-opacity-20 p-3 rounded-lg">
                            <span class="text-blue-300">📊 อันดับปัจจุบัน</span>
                            <span class="font-bold text-blue-200">#<?php echo $player_rank; ?></span>
                        </div>
                        
                        <div class="flex justify-between items-center bg-purple-600 bg-opacity-20 p-3 rounded-lg">
                            <span class="text-purple-300">🎯 เกมทั้งหมด</span>
                            <span class="font-bold text-purple-200"><?php echo $total_games; ?> เกม</span>
                        </div>
                        
                        <div class="flex justify-between items-center bg-indigo-600 bg-opacity-20 p-3 rounded-lg">
                            <span class="text-indigo-300">📈 คะแนนเฉลี่ย</span>
                            <span class="font-bold text-indigo-200"><?php echo $avg_score; ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Leaderboard Section -->
            <div class="lg:col-span-2">
                <div class="bg-gradient-to-br from-oxford-blue to-blue-800 p-6 rounded-2xl book-shadow stat-card">
                    <div class="text-center mb-6">
                        <h2 class="text-3xl font-bold gold-gradient mb-2">🏆 อันดับนักเรียนยอดเยี่ยม</h2>
                        <p class="text-gray-300">Top 5 คะแนนสูงสุด</p>
                    </div>
                    
                    <div class="space-y-4">
                        <?php foreach ($top5_players as $index => $player): ?>
                            <?php 
                            $rank = $index + 1;
                            $isCurrentPlayer = $player['username'] === $username;
                            
                            // กำหนดสีและไอคอนตามอันดับ
                            $rankColors = [
                                1 => ['bg' => 'from-yellow-500 to-yellow-600', 'text' => 'text-yellow-100', 'icon' => '🥇'],
                                2 => ['bg' => 'from-gray-400 to-gray-500', 'text' => 'text-gray-100', 'icon' => '🥈'],
                                3 => ['bg' => 'from-orange-500 to-orange-600', 'text' => 'text-orange-100', 'icon' => '🥉'],
                                4 => ['bg' => 'from-blue-500 to-blue-600', 'text' => 'text-blue-100', 'icon' => '🏅'],
                                5 => ['bg' => 'from-purple-500 to-purple-600', 'text' => 'text-purple-100', 'icon' => '🏅']
                            ];
                            
                            $colors = $rankColors[$rank] ?? ['bg' => 'from-gray-500 to-gray-600', 'text' => 'text-gray-100', 'icon' => '🏅'];
                            ?>
                            
                            <div class="bg-gradient-to-r <?php echo $colors['bg']; ?> p-4 rounded-xl rank-badge 
                                        <?php echo $isCurrentPlayer ? 'ring-4 ring-oxford-gold ring-opacity-50' : ''; ?>">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-4">
                                        <div class="text-2xl <?php echo $rank <= 3 ? 'trophy-animation' : ''; ?>">
                                            <?php echo $colors['icon']; ?>
                                        </div>
                                        <div>
                                            <h3 class="text-xl font-bold <?php echo $colors['text']; ?>">
                                                <?php echo htmlspecialchars($player['username']); ?>
                                                <?php if ($isCurrentPlayer): ?>
                                                    <span class="text-sm bg-oxford-gold text-oxford-blue px-2 py-1 rounded-full ml-2">คุณ</span>
                                                <?php endif; ?>
                                            </h3>
                                            <p class="text-sm <?php echo $colors['text']; ?> opacity-80">
                                                เล่นไปแล้ว <?php echo $player['total_games']; ?> เกม
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-2xl font-bold <?php echo $colors['text']; ?>">
                                            <?php echo $player['high_score']; ?>
                                        </div>
                                        <div class="text-sm <?php echo $colors['text']; ?> opacity-80">
                                            คะแนน
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Global Stats -->
        <div class="mt-8 bg-gradient-to-r from-oxford-blue to-blue-800 p-6 rounded-2xl book-shadow stat-card">
            <h2 class="text-2xl font-bold gold-gradient text-center mb-6">📈 สถิติโดยรวม</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="text-center bg-green-600 bg-opacity-20 p-4 rounded-xl">
                    <div class="text-2xl mb-2">🎮</div>
                    <div class="text-2xl font-bold text-green-200">
                        <?php echo $total_stats['total_games_played'] ?? 0; ?>
                    </div>
                    <div class="text-sm text-green-300">เกมทั้งหมด</div>
                </div>
                
                <div class="text-center bg-blue-600 bg-opacity-20 p-4 rounded-xl">
                    <div class="text-2xl mb-2">📊</div>
                    <div class="text-2xl font-bold text-blue-200">
                        <?php echo round($total_stats['overall_avg_score'] ?? 0, 1); ?>
                    </div>
                    <div class="text-sm text-blue-300">คะแนนเฉลี่ย</div>
                </div>
                
                <div class="text-center bg-yellow-600 bg-opacity-20 p-4 rounded-xl">
                    <div class="text-2xl mb-2">🏆</div>
                    <div class="text-2xl font-bold text-yellow-200">
                        <?php echo $total_stats['highest_score_ever'] ?? 0; ?>
                    </div>
                    <div class="text-sm text-yellow-300">สถิติสูงสุด</div>
                </div>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="mt-8 flex justify-center space-x-4">
            <a href="index.php" 
               class="bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-500 hover:to-emerald-500 text-white px-8 py-3 rounded-xl font-semibold transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105">
                🎮 กลับไปเล่นเกม
            </a>
            
            <a href="logout.php" 
               class="bg-gradient-to-r from-red-600 to-pink-600 hover:from-red-500 hover:to-pink-500 text-white px-8 py-3 rounded-xl font-semibold transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105">
                🚪 ออกจากระบบ
            </a>
        </div>
    </div>
    
    <!-- Auto refresh script -->
    <script>
        // Auto refresh every 30 seconds to update leaderboard
        setInterval(function() {
            location.reload();
        }, 30000);
        
        // Add smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>