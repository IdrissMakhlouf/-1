<?php
/**
 * Book Trip - Process Trip Booking
 * Heritage Platform - Algeria Cultural Heritage
 */

require_once '../config/db.php';

// Check if user is logged in
Auth::requireLogin();

$user_id = Auth::getCurrentUserId();

// Handle booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_trip'])) {
    $trip_id = intval($_POST['trip_id']);
    
    // Check if trip exists and is available
    $tripQuery = $db->prepare("SELECT * FROM smart_trips WHERE trip_id = ? AND (user_id IS NULL OR user_id = 0)");
    $tripQuery->execute([$trip_id]);
    $trip = $tripQuery->fetch();
    
    if (!$trip) {
        $_SESSION['error'] = "الرحلة غير متاحة للحجز";
        header('Location: smart_trips.php');
        exit();
    }
    
    // Check if user already booked this trip
    $checkQuery = $db->prepare("SELECT trip_id FROM smart_trips WHERE trip_id = ? AND user_id = ?");
    $checkQuery->execute([$trip_id, $user_id]);
    
    if ($checkQuery->rowCount() > 0) {
        $_SESSION['error'] = "لقد قمت بحجز هذه الرحلة مسبقاً";
        header('Location: smart_trips.php');
        exit();
    }
    
    // Check available spots
    $spotsLeft = $trip['max_participants'] - ($trip['current_bookings'] ?? 0);
    if ($spotsLeft <= 0) {
        $_SESSION['error'] = "عذراً، اكتمل العدد المطلوب لهذه الرحلة";
        header('Location: smart_trips.php');
        exit();
    }
    
    // Create a copy of the trip for the user
    $insertQuery = $db->prepare("
        INSERT INTO smart_trips (user_id, state_id, trip_name, sites, hotels, restaurants, 
                                duration_days, estimated_cost, description, departure_date, 
                                return_date, trip_type, included_services, excluded_services, 
                                meeting_point, guide_name, guide_phone, max_participants, created_at)
        SELECT ?, state_id, trip_name, sites, hotels, restaurants, duration_days, estimated_cost, 
               description, departure_date, return_date, trip_type, included_services, 
               excluded_services, meeting_point, guide_name, guide_phone, max_participants, NOW()
        FROM smart_trips WHERE trip_id = ?
    ");
    
    if ($insertQuery->execute([$user_id, $trip_id])) {
        // Update current bookings count on the original trip
        $updateQuery = $db->prepare("UPDATE smart_trips SET current_bookings = COALESCE(current_bookings, 0) + 1 WHERE trip_id = ?");
        $updateQuery->execute([$trip_id]);
        
        $_SESSION['success'] = "تم حجز الرحلة بنجاح! يمكنك الاطلاع على تفاصيل الرحلة في قسم 'رحلاتي'";
    } else {
        $_SESSION['error'] = "حدث خطأ أثناء حجز الرحلة";
    }
    
    header('Location: smart_trips.php');
    exit();
}

// Redirect if accessed directly
header('Location: smart_trips.php');
exit();
?>