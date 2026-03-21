<?php
declare(strict_types=1);
namespace App\Controllers\Frontend;
use App\System\NexaController;

/**
 * ExampleController - Demonstrates NexaJs usage
 * Shows how to send data from PHP to JavaScript
 */
class ExampleController extends NexaController
{
    /**
     * Example page with dynamic data sent to JavaScript
     */
    public function index(array $params = []): void
    {
        // Setup basic page variables
        $this->assignVars([
            'page_title' => 'NexaJs Example - Dynamic Data',
            'page_description' => 'Example of sending data from PHP to JavaScript',
            'current_page' => 'example',
            'is_public_page' => true
        ]);

        // ========================================================================
        // MENGIRIM DATA KE JAVASCRIPT VIA setJsController()
        // ========================================================================
        
        // Data yang akan dikirim ke JavaScript
        $jsData = [
            'user_info' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'role' => 'developer',
                'last_login' => date('Y-m-d H:i:s')
            ],
            'page_data' => [
                'current_time' => date('Y-m-d H:i:s'),
                'server_info' => [
                    'php_version' => PHP_VERSION,
                    'server_time' => time(),
                    'memory_usage' => memory_get_usage(true)
                ],
            ],
            'dynamic_content' => [
                'articles' => [
                    ['id' => 1, 'title' => 'Getting Started with NexaUI', 'views' => 1250],
                    ['id' => 2, 'title' => 'Advanced JavaScript Integration', 'views' => 890],
                    ['id' => 3, 'title' => 'Real-time Data Updates', 'views' => 567]
                ],
                'notifications' => [
                    ['type' => 'info', 'message' => 'Welcome to NexaJs example!'],
                    ['type' => 'success', 'message' => 'Data loaded successfully']
                ]
            ],
            'config' => [
                'api_endpoint' => '/api/v1',
                'refresh_interval' => 5000,
                'debug_mode' => true
            ]
        ];

        // Kirim data ke JavaScript
        $this->setJsController($jsData);
        
        // ========================================================================
        // ALTERNATIVE: Mengirim data dengan metadata tambahan
        // ========================================================================
        
        // Jika ingin menambahkan metadata
        $this->setJsController([
            'data' => $jsData,
            'metadata' => [
                'sent_from' => 'ExampleController::index',
                'timestamp' => date('Y-m-d H:i:s'),
                'version' => '1.0.0'
            ]
        ]);
    }

    /**
     * Example with real-time data updates
     */
    public function realtime(array $params = []): void
    {
        $this->assignVars([
            'page_title' => 'Real-time Data Example',
            'page_description' => 'Example of real-time data updates',
            'current_page' => 'example',
            'current_section' => 'realtime'
        ]);

        // Simulasi data real-time
        $realtimeData = [
            'live_stats' => [
                'online_users' => rand(100, 500),
                'active_sessions' => rand(50, 200),
                'requests_per_minute' => rand(1000, 5000)
            ],
            'system_status' => [
                'cpu_usage' => rand(20, 80),
                'memory_usage' => rand(30, 90),
                'disk_usage' => rand(40, 85)
            ],
            'recent_activities' => [
                ['user' => 'user1', 'action' => 'login', 'time' => date('H:i:s')],
                ['user' => 'user2', 'action' => 'logout', 'time' => date('H:i:s', time()-60)],
                ['user' => 'user3', 'action' => 'comment', 'time' => date('H:i:s', time()-120)]
            ]
        ];

        $this->setJsController($realtimeData);
    }

    /**
     * Example with user-specific data
     */
    public function user(array $params = []): void
    {
        $userId = $params['id'] ?? 1;
        
        $this->assignVars([
            'page_title' => 'User Profile Example',
            'page_description' => 'Example with user-specific data',
            'current_page' => 'example',
            'current_section' => 'user'
        ]);

        // Simulasi data user dari database
        $userData = [
            'user_profile' => [
                'id' => $userId,
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'avatar' => '/assets/images/avatars/user1.jpg',
                'join_date' => '2024-01-15'
            ],
            'user_preferences' => [
                'theme' => 'dark',
                'language' => 'en',
                'notifications' => true,
                'timezone' => 'Asia/Jakarta'
            ],
            'user_activity' => [
                'last_login' => date('Y-m-d H:i:s'),
                'login_count' => 42,
                'posts_count' => 15,
                'comments_count' => 28
            ]
        ];

        $this->setJsController($userData);
    }

    /**
     * Example with form data
     */
    public function form(array $params = []): void
    {
        $this->assignVars([
            'page_title' => 'Form Example',
            'page_description' => 'Example with form validation data',
            'current_page' => 'example',
            'current_section' => 'form'
        ]);

        // Data untuk form validation dan UI
        $formData = [
            'form_config' => [
                'fields' => [
                    'name' => [
                        'required' => true,
                        'min_length' => 2,
                        'max_length' => 50,
                        'pattern' => '^[a-zA-Z\s]+$'
                    ],
                    'email' => [
                        'required' => true,
                        'type' => 'email',
                        'pattern' => '^[^\s@]+@[^\s@]+\.[^\s@]+$'
                    ],
                    'age' => [
                        'required' => false,
                        'min' => 18,
                        'max' => 100,
                        'type' => 'number'
                    ]
                ],
                'validation_messages' => [
                    'name_required' => 'Name is required',
                    'name_length' => 'Name must be between 2 and 50 characters',
                    'email_invalid' => 'Please enter a valid email address',
                    'age_range' => 'Age must be between 18 and 100'
                ]
            ],
            'form_data' => [
                'submitted' => false,
                'errors' => [],
                'success_message' => ''
            ]
        ];

        $this->setJsController($formData);
    }
} 