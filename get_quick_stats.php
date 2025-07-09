<?php
// get_quick_stats.php - AJAX endpoint for quick stats
session_start();
include 'db.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$user_id = intval($_SESSION['user_id']);

try {
    // Get player's high score
    $high_score_query = $conn->prepare("SELECT MAX(score) as high_score FROM scores WHERE user_id = ?");
    $high_score_query->bind_param("i", $user_id);
    $high_score_query->execute();
    $high_score_result = $high_score_query->get_result();
    $high_score_data = $high_score_result->fetch_assoc();
    
    // Get player's rank
    $player_high_score = $high_score_data['high_score'] ?? 0;
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
    
    // Get total games played
    $games_query = $conn->prepare("SELECT COUNT(*) as total_games FROM scores WHERE user_id = ?");
    $games_query->bind_param("i", $user_id);
    $games_query->execute();
    $games_result = $games_query->get_result();
    $total_games = $games_result->fetch_assoc()['total_games'] ?? 0;
    
    // Return data as JSON
    echo json_encode([
        'high_score' => $player_high_score,
        'current_score' => $_SESSION['score'] ?? 0,
        'rank' => $player_rank,
        'total_games' => $total_games,
        'success' => true
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>