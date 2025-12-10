<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"));
$response = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($data->action)) {
        switch ($data->action) {
            case 'register':
                // Validate input
                if (!empty($data->name) && !empty($data->email) && !empty($data->password)) {
                    $name = $conn->real_escape_string($data->name);
                    $email = $conn->real_escape_string($data->email);
                    $password = password_hash($data->password, PASSWORD_DEFAULT);
                    
                    // Check if email already exists
                    $check = $conn->query("SELECT id FROM users WHERE email = '$email'");
                    
                    if ($check->num_rows > 0) {
                        $response = [
                            'status' => 'error',
                            'message' => 'Email already exists.'
                        ];
                    } else {
                        // Insert new user
                        $sql = "INSERT INTO users (name, email, password) VALUES ('$name', '$email', '$password')";
                        
                        if ($conn->query($sql)) {
                            $response = [
                                'status' => 'success',
                                'message' => 'Registration successful.'
                            ];
                        } else {
                            $response = [
                                'status' => 'error',
                                'message' => 'Registration failed. Please try again.'
                            ];
                        }
                    }
                } else {
                    $response = [
                        'status' => 'error',
                        'message' => 'Please fill in all required fields.'
                    ];
                }
                break;
                
            case 'login':
                if (!empty($data->email) && !empty($data->password)) {
                    $email = $conn->real_escape_string($data->email);
                    $password = $data->password;
                    
                    $result = $conn->query("SELECT * FROM users WHERE email = '$email'");
                    
                    if ($result->num_rows > 0) {
                        $user = $result->fetch_assoc();
                        
                        if (password_verify($password, $user['password'])) {
                            // Start session and set session variables
                            session_start();
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['user_name'] = $user['name'];
                            $_SESSION['user_email'] = $user['email'];
                            
                            $response = [
                                'status' => 'success',
                                'message' => 'Login successful.',
                                'user' => [
                                    'id' => $user['id'],
                                    'name' => $user['name'],
                                    'email' => $user['email']
                                ]
                            ];
                        } else {
                            $response = [
                                'status' => 'error',
                                'message' => 'Invalid email or password.'
                            ];
                        }
                    } else {
                        $response = [
                            'status' => 'error',
                            'message' => 'User not found.'
                        ];
                    }
                } else {
                    $response = [
                        'status' => 'error',
                        'message' => 'Please provide email and password.'
                    ];
                }
                break;
                
            case 'logout':
                session_start();
                session_destroy();
                $response = [
                    'status' => 'success',
                    'message' => 'Logged out successfully.'
                ];
                break;
                
            default:
                $response = [
                    'status' => 'error',
                    'message' => 'Invalid action.'
                ];
        }
    } else {
        $response = [
            'status' => 'error',
            'message' => 'No action specified.'
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
