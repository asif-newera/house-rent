<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"));
$response = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($data->name) && !empty($data->email) && !empty($data->message)) {
        $name = $conn->real_escape_string($data->name);
        $email = $conn->real_escape_string($data->email);
        $phone = !empty($data->phone) ? $conn->real_escape_string($data->phone) : '';
        $message = $conn->real_escape_string($data->message);
        $property_id = !empty($data->property_id) ? intval($data->property_id) : null;
        
        $sql = "INSERT INTO contact_messages (name, email, phone, message, property_id) 
                VALUES (?, ?, ?, ?, ?)";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $name, $email, $phone, $message, $property_id);
        
        if ($stmt->execute()) {
            // Send email notification (uncomment and configure if needed)
            /*
            $to = "your-email@example.com";
            $subject = "New Contact Form Submission";
            $message = "Name: $name\nEmail: $email\nPhone: $phone\n\nMessage:\n$message";
            $headers = "From: $email";
            
            mail($to, $subject, $message, $headers);
            */
            
            $response = [
                'status' => 'success',
                'message' => 'Your message has been sent successfully. We will contact you soon.'
            ];
        } else {
            $response = [
                'status' => 'error',
                'message' => 'Failed to send message. Please try again.'
            ];
        }
    } else {
        $response = [
            'status' => 'error',
            'message' => 'Please fill in all required fields.'
        ];
    }
} else {
    $response = [
        'status' => 'error',
        'message' => 'Invalid request method.'
    ];
}

echo json_encode($response);
?>
