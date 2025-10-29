<?php
// Add at the very top
if (file_exists(__DIR__ . '/../.env.production')) {
    $env = parse_ini_file(__DIR__ . '/../.env.production');
    foreach ($env as $key => $value) {
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}

// Error handling for production
if (getenv('APP_ENV') === 'production') {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}


session_start();

// Clear toast after it's been displayed
if (isset($_SESSION['toast_displayed'])) {
    unset($_SESSION['toast']);
    unset($_SESSION['toast_displayed']);
}

// Clear form errors and old input after they've been displayed
if (isset($_SESSION['form_errors_displayed'])) {
    unset($_SESSION['form_errors']);
    unset($_SESSION['old_input']);
    unset($_SESSION['form_errors_displayed']);
}

// Mark toast for display
if (isset($_SESSION['toast'])) {
    $_SESSION['toast_displayed'] = true;
}

// Mark form errors for display
if (isset($_SESSION['form_errors'])) {
    $_SESSION['form_errors_displayed'] = true;
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/utils/storage.php';
require_once __DIR__ . '/../src/utils/validation.php';

// Initialize demo data
initializeDemoData();

// Initialize Twig first
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../src/templates');
$twig = new \Twig\Environment($loader, [
    'cache' => __DIR__ . '/../cache',
    'auto_reload' => true,
]);

// Simple routing
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);

// Variables that will be passed to templates
$templateData = [];

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($path) {
        case '/auth/login':
            handleLogin();
            break;
        case '/auth/signup':
            handleSignup();
            break;
        case '/auth/logout':
            handleLogout();
            break;
        case '/tickets/create':
            handleCreateTicket();
            break;
        case '/tickets/update':
            handleUpdateTicket();
            break;
        case '/tickets/delete':
            handleDeleteTicket();
            break;
        default:
            http_response_code(404);
            break;
    }
    exit;
}

// Handle GET requests
switch ($path) {
    case '/':
        $template = 'landing.twig';
        break;
    case '/auth/login':
        $template = 'auth/login.twig';
        break;
    case '/auth/signup':
        $template = 'auth/signup.twig';
        break;
    case '/dashboard':
        if (!isAuthenticated()) {
            header('Location: /auth/login');
            exit;
        }
        $template = 'dashboard/dashboard.twig';
        
        // Get dashboard stats
        $tickets = getStorage('ticketapp_tickets') ?? [];
        $userTickets = array_filter($tickets, function($ticket) {
            return isset($_SESSION['ticketapp_session']['user']['id']) && 
                   $ticket['userId'] === $_SESSION['ticketapp_session']['user']['id'];
        });
        
        $templateData['stats'] = [
            'total' => count($userTickets),
            'open' => count(array_filter($userTickets, function($ticket) {
                return $ticket['status'] === 'open';
            })),
            'inProgress' => count(array_filter($userTickets, function($ticket) {
                return $ticket['status'] === 'in_progress';
            })),
            'closed' => count(array_filter($userTickets, function($ticket) {
                return $ticket['status'] === 'closed';
            }))
        ];
        
        // Get recent activity for the current user
        $allActivities = getStorage('ticketapp_activity') ?? [];
        $userActivities = array_filter($allActivities, function($activity) {
            return isset($_SESSION['ticketapp_session']['user']['id']) && 
                   $activity['userId'] === $_SESSION['ticketapp_session']['user']['id'];
        });
        
        // Sort activities by timestamp (newest first) and get last 10
        usort($userActivities, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        
        $templateData['recentActivity'] = array_slice($userActivities, 0, 10);
        break;
    case '/tickets':
        if (!isAuthenticated()) {
            header('Location: /auth/login');
            exit;
        }
        
        // Get user's tickets
        $allTickets = getStorage('ticketapp_tickets') ?? [];
        $userTickets = array_filter($allTickets, function($ticket) {
            return isset($_SESSION['ticketapp_session']['user']['id']) && 
                   $ticket['userId'] === $_SESSION['ticketapp_session']['user']['id'];
        });
        
        // Sort by creation date (newest first)
        usort($userTickets, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        $template = 'tickets/ticket-management.twig';
        $templateData['tickets'] = array_values($userTickets);
        break;
    default:
        http_response_code(404);
        $template = '404.twig';
        break;
}

// Add global variables to Twig
$twig->addGlobal('session', $_SESSION);
$twig->addGlobal('app', [
    'request' => [
        'pathinfo' => $path
    ]
]);

// Render the template with data
echo $twig->render($template, $templateData);

// Authentication functions
function isAuthenticated() {
    if (!isset($_SESSION['ticketapp_session'])) {
        return false;
    }
    
    $session = $_SESSION['ticketapp_session'];
    if (strtotime($session['expires_at']) < time()) {
        unset($_SESSION['ticketapp_session']);
        return false;
    }
    
    return true;
}

function handleLogin() {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $users = getStorage('ticketapp_users') ?? [];
    $user = array_filter($users, function($u) use ($email, $password) {
        return $u['email'] === $email && $u['password'] === $password;
    });
    
    if (!empty($user)) {
        $user = reset($user);
        $_SESSION['ticketapp_session'] = [
            'token' => 'demo-token',
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'name' => $user['name']
            ],
            'expires_at' => date('Y-m-d H:i:s', time() + 24 * 60 * 60)
        ];
        
        $_SESSION['toast'] = [
            'type' => 'success',
            'title' => 'Welcome back!',
            'message' => 'You have been successfully logged in.'
        ];
        
        header('Location: /dashboard');
    } else {
        $_SESSION['toast'] = [
            'type' => 'error',
            'title' => 'Login Failed',
            'message' => 'Invalid email or password'
        ];
        $_SESSION['form_errors'] = ['Invalid email or password'];
        $_SESSION['old_input'] = $_POST;
        header('Location: /auth/login');
    }
}

function handleSignup() {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    
    $errors = [];
    
    // Validation
    if (empty($name) || strlen($name) < 2) {
        $errors[] = 'Name must be at least 2 characters';
    }
    
    if (!validateEmail($email)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match';
    }
    
    // Check if user exists
    $users = getStorage('ticketapp_users') ?? [];
    $existingUser = array_filter($users, function($u) use ($email) {
        return $u['email'] === $email;
    });
    
    if (!empty($existingUser)) {
        $errors[] = 'User with this email already exists';
    }
    
    if (empty($errors)) {
        $userId = (string) time();
        $newUser = [
            'id' => $userId,
            'name' => $name,
            'email' => $email,
            'password' => $password
        ];
        
        $users[] = $newUser;
        setStorage('ticketapp_users', $users);
        
        $_SESSION['ticketapp_session'] = [
            'token' => 'demo-token',
            'user' => [
                'id' => $userId,
                'email' => $email,
                'name' => $name
            ],
            'expires_at' => date('Y-m-d H:i:s', time() + 24 * 60 * 60)
        ];
        
        $_SESSION['toast'] = [
            'type' => 'success',
            'title' => 'Welcome!',
            'message' => 'Your account has been created successfully.'
        ];
        
        header('Location: /dashboard');
    } else {
        $_SESSION['toast'] = [
            'type' => 'error',
            'title' => 'Signup Failed',
            'message' => implode(', ', $errors)
        ];
        $_SESSION['form_errors'] = $errors;
        $_SESSION['old_input'] = $_POST;
        header('Location: /auth/signup');
    }
}

function handleLogout() {
    unset($_SESSION['ticketapp_session']);
    $_SESSION['toast'] = [
        'type' => 'success',
        'title' => 'Logged out',
        'message' => 'You have been successfully logged out.'
    ];
    header('Location: /');
}

// Ticket handling functions
function handleCreateTicket() {
    if (!isAuthenticated()) {
        http_response_code(401);
        exit;
    }

    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $status = $_POST['status'] ?? 'open';
    $priority = $_POST['priority'] ?? '';
    $reporter = $_POST['reporter'] ?? '';

    $errors = [];
    
    // Validation
    $titleError = validateTicketTitle($title);
    if ($titleError) $errors[] = $titleError;
    
    $descError = validateTicketDescription($description);
    if ($descError) $errors[] = $descError;

    if (empty($errors)) {
        $tickets = getStorage('ticketapp_tickets') ?? [];
        $newTicket = [
            'id' => (string) time(),
            'title' => $title,
            'description' => $description,
            'status' => $status,
            'priority' => $priority ?: null,
            'reporter' => $reporter ?: null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'userId' => $_SESSION['ticketapp_session']['user']['id']
        ];

        $tickets[] = $newTicket;
        setStorage('ticketapp_tickets', $tickets);

        // Add activity
        $activities = getStorage('ticketapp_activity') ?? [];
        $activities[] = [
            'id' => (string) time(),
            'type' => 'created',
            'ticketId' => $newTicket['id'],
            'title' => $newTicket['title'],
            'timestamp' => $newTicket['created_at'],
            'userId' => $newTicket['userId']
        ];
        setStorage('ticketapp_activity', array_slice($activities, -10)); // Keep last 10

        $_SESSION['toast'] = [
            'type' => 'success',
            'title' => 'Ticket Created',
            'message' => 'The ticket has been created successfully.'
        ];

        echo json_encode(['success' => true]);
    } else {
        $_SESSION['toast'] = [
            'type' => 'error',
            'title' => 'Validation Error',
            'message' => implode(', ', $errors)
        ];
        echo json_encode(['success' => false, 'errors' => $errors]);
    }
}

function handleUpdateTicket() {
    if (!isAuthenticated()) {
        http_response_code(401);
        exit;
    }

    $ticketId = $_POST['id'] ?? '';
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $status = $_POST['status'] ?? 'open';
    $priority = $_POST['priority'] ?? '';
    $reporter = $_POST['reporter'] ?? '';

    $errors = [];
    
    // Validation
    $titleError = validateTicketTitle($title);
    if ($titleError) $errors[] = $titleError;
    
    $descError = validateTicketDescription($description);
    if ($descError) $errors[] = $descError;

    if (empty($errors)) {
        $tickets = getStorage('ticketapp_tickets') ?? [];
        $updated = false;

        foreach ($tickets as &$ticket) {
            if ($ticket['id'] === $ticketId && $ticket['userId'] === $_SESSION['ticketapp_session']['user']['id']) {
                $ticket['title'] = $title;
                $ticket['description'] = $description;
                $ticket['status'] = $status;
                $ticket['priority'] = $priority ?: null;
                $ticket['reporter'] = $reporter ?: null;
                $ticket['updated_at'] = date('Y-m-d H:i:s');
                $updated = true;
                break;
            }
        }

        if ($updated) {
            setStorage('ticketapp_tickets', $tickets);

            // Add activity
            $activities = getStorage('ticketapp_activity') ?? [];
            $activities[] = [
                'id' => (string) time(),
                'type' => 'updated',
                'ticketId' => $ticketId,
                'title' => $title,
                'timestamp' => date('Y-m-d H:i:s'),
                'userId' => $_SESSION['ticketapp_session']['user']['id']
            ];
            setStorage('ticketapp_activity', array_slice($activities, -10));

            $_SESSION['toast'] = [
                'type' => 'success',
                'title' => 'Ticket Updated',
                'message' => 'The ticket has been updated successfully.'
            ];

            echo json_encode(['success' => true]);
        } else {
            $_SESSION['toast'] = [
                'type' => 'error',
                'title' => 'Update Failed',
                'message' => 'Ticket not found'
            ];
            echo json_encode(['success' => false, 'errors' => ['Ticket not found']]);
        }
    } else {
        $_SESSION['toast'] = [
            'type' => 'error',
            'title' => 'Validation Error',
            'message' => implode(', ', $errors)
        ];
        echo json_encode(['success' => false, 'errors' => $errors]);
    }
}

function handleDeleteTicket() {
    if (!isAuthenticated()) {
        http_response_code(401);
        exit;
    }

    $ticketId = $_POST['id'] ?? '';

    if ($ticketId) {
        $tickets = getStorage('ticketapp_tickets') ?? [];
        $ticketToDelete = null;
        $updatedTickets = [];

        foreach ($tickets as $ticket) {
            if ($ticket['id'] === $ticketId && $ticket['userId'] === $_SESSION['ticketapp_session']['user']['id']) {
                $ticketToDelete = $ticket;
            } else {
                $updatedTickets[] = $ticket;
            }
        }

        if ($ticketToDelete) {
            setStorage('ticketapp_tickets', $updatedTickets);

            // Add activity
            $activities = getStorage('ticketapp_activity') ?? [];
            $activities[] = [
                'id' => (string) time(),
                'type' => 'deleted',
                'ticketId' => $ticketToDelete['id'],
                'title' => $ticketToDelete['title'],
                'timestamp' => date('Y-m-d H:i:s'),
                'userId' => $_SESSION['ticketapp_session']['user']['id']
            ];
            setStorage('ticketapp_activity', array_slice($activities, -10));

            $_SESSION['toast'] = [
                'type' => 'success',
                'title' => 'Ticket Deleted',
                'message' => 'The ticket has been deleted successfully.'
            ];

            echo json_encode(['success' => true]);
        } else {
            $_SESSION['toast'] = [
                'type' => 'error',
                'title' => 'Delete Failed',
                'message' => 'Ticket not found'
            ];
            echo json_encode(['success' => false, 'errors' => ['Ticket not found']]);
        }
    }
}

function initializeDemoData() {
    $users = getStorage('ticketapp_users') ?? [];
    $demoUserExists = array_filter($users, function($u) {
        return $u['email'] === 'demo@example.com';
    });
    
    if (empty($demoUserExists)) {
        $users[] = [
            'id' => '1',
            'email' => 'demo@example.com',
            'password' => 'password',
            'name' => 'Demo User'
        ];
        setStorage('ticketapp_users', $users);
    }
    
    // Also initialize tickets storage
    $tickets = getStorage('ticketapp_tickets');
    if ($tickets === null) {
        setStorage('ticketapp_tickets', []);
    }
    
    // Initialize activity storage
    $activity = getStorage('ticketapp_activity');
    if ($activity === null) {
        setStorage('ticketapp_activity', []);
        
        // Add some demo activities for initial data
        $demoActivities = [
            [
                'id' => '1',
                'type' => 'created',
                'ticketId' => 'demo1',
                'title' => 'Welcome to Ticket System',
                'timestamp' => date('Y-m-d H:i:s', time() - 3600),
                'userId' => '1'
            ]
        ];
        setStorage('ticketapp_activity', $demoActivities);
    }
}