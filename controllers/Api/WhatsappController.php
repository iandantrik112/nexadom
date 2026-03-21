<?php
declare(strict_types=1);
namespace App\Controllers\Api;
use App\System\NexaController;
use Exception;

/**
 * WhatsApp Controller - API untuk mengirim pesan WhatsApp via Fonnte
 * 
 * Endpoints:
 * - GET  /api/whatsapp          - Info & dokumentasi API
 * - POST /api/whatsapp/fonnte   - Kirim pesan via Fonnte (RECOMMENDED)
 * - POST /api/whatsapp/fonnteOrder - Kirim notifikasi order via Fonnte
 * - POST /api/whatsapp/fonnteBulk  - Kirim ke multiple nomor via Fonnte
 * 
 * Setup Fonnte (5 menit):
 * 1. Daftar: https://fonnte.com
 * 2. Scan QR dari WhatsApp
 * 3. Copy token
 * 4. Done!
 */
class WhatsappController extends NexaController
{
    /**
     * Index endpoint - Dokumentasi API WhatsApp
     * Method: GET /api/whatsapp
     */
    public function index(): array
    {
        return [
            'status' => 'success',
            'message' => 'WhatsApp API v2.0 - Powered by Fonnte',
            'timestamp' => time(),
            'endpoints' => [
                'GET /api/whatsapp' => 'API Documentation',
                'POST /api/whatsapp/fonnte' => 'Send WhatsApp message via Fonnte',
                'POST /api/whatsapp/fonnteOrder' => 'Send order notification via Fonnte',
                'POST /api/whatsapp/fonnteBulk' => 'Send bulk messages via Fonnte'
            ],
            'provider' => [
                'name' => 'Fonnte',
                'website' => 'https://fonnte.com',
                'pricing' => 'Rp 200.000/bulan (unlimited pesan)',
                'setup_time' => '5 menit (scan QR + copy token)'
            ],
            'documentation' => [
                'fonnte' => [
                    'method' => 'POST',
                    'description' => 'Kirim pesan WhatsApp via Fonnte (otomatis terkirim)',
                    'required_fields' => [
                        'phone' => 'Nomor tujuan (format: 08xxx atau 628xxx)',
                        'message' => 'Isi pesan yang akan dikirim'
                    ],
                    'example' => [
                        'phone' => '081234567890',
                        'message' => 'Halo, pesanan Anda sudah siap!'
                    ],
                    'response_example' => [
                        'status' => 'success',
                        'message' => 'Pesan WhatsApp berhasil dikirim via Fonnte',
                        'data' => [
                            'message_id' => [140908110],
                            'to' => '6281234567890',
                            'sent_at' => '2026-01-28 03:08:48',
                            'provider' => 'Fonnte'
                        ]
                    ]
                ],
                'fonnteOrder' => [
                    'method' => 'POST',
                    'description' => 'Kirim notifikasi order dengan format rapi',
                    'required_fields' => [
                        'phone' => 'Nomor tujuan',
                        'order_number' => 'Nomor order'
                    ],
                    'optional_fields' => [
                        'buyer_name' => 'Nama pembeli',
                        'total_amount' => 'Total pembayaran',
                        'shipping_address' => 'Alamat pengiriman',
                        'items' => 'Daftar item order',
                        'shipping_cost' => 'Biaya kirim',
                        'payment_method' => 'Metode pembayaran'
                    ]
                ],
                'fonnteBulk' => [
                    'method' => 'POST',
                    'description' => 'Kirim pesan ke banyak nomor sekaligus',
                    'required_fields' => [
                        'message' => 'Isi pesan',
                        'recipients' => 'Array of [phone, name]'
                    ],
                    'example' => [
                        'message' => 'Promo spesial hari ini!',
                        'recipients' => [
                            ['phone' => '081234567890', 'name' => 'User 1'],
                            ['phone' => '082111222333', 'name' => 'User 2']
                        ]
                    ]
                ]
            ]
        ];
    }
    
    /**
     * Fonnte - Kirim WhatsApp via Fonnte (SUPER MUDAH!)
     * Method: POST /api/whatsapp/fonnte
     * 
     * Keunggulan:
     * - Setup cuma 5 menit (scan QR)
     * - Cuma butuh 1 token
     * - Kirim otomatis tanpa user klik
     * - Harga terjangkau (Rp 200rb/bulan unlimited)
     * 
     * Body JSON:
     * {
     *   "phone": "085219459790",
     *   "message": "Pesan Anda"
     * }
     */
    public function fonnte($data = [], $params = []): array
    {
        try {
            // Validasi input
            if (empty($data['phone'])) {
                return [
                    'status' => 'error',
                    'message' => 'Nomor tujuan (phone) wajib diisi',
                    'timestamp' => time()
                ];
            }
            
            if (empty($data['message'])) {
                return [
                    'status' => 'error',
                    'message' => 'Pesan (message) wajib diisi',
                    'timestamp' => time()
                ];
            }
            
            // Format nomor tujuan
            $phoneDestination = $this->formatPhoneNumber($data['phone']);
            
            // Get Fonnte token
            $token = getenv('FONNTE_TOKEN') ?: '49d4wJKAuUKsSqpTrdHU';
            
            // Kirim via Fonnte API
            $result = $this->sendFonnteMessage($token, $phoneDestination, $data['message']);
            
            if ($result['success']) {
                return [
                    'status' => 'success',
                    'message' => 'Pesan WhatsApp berhasil dikirim via Fonnte',
                    'timestamp' => time(),
                    'data' => [
                        'message_id' => $result['message_id'] ?? null,
                        'to' => $phoneDestination,
                        'sent_at' => date('Y-m-d H:i:s'),
                        'provider' => 'Fonnte'
                    ]
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => $result['error'] ?? 'Gagal mengirim pesan via Fonnte',
                    'timestamp' => time(),
                    'details' => $result['details'] ?? null
                ];
            }
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage(),
                'timestamp' => time()
            ];
        }
    }
    
    /**
     * Fonnte Order - Kirim notifikasi order via Fonnte
     * Method: POST /api/whatsapp/fonnteOrder
     */
    public function fonnteOrder($data = [], $params = []): array
    {
        try {
            if (empty($data['phone']) || empty($data['order_number'])) {
                return [
                    'status' => 'error',
                    'message' => 'phone dan order_number wajib diisi',
                    'timestamp' => time()
                ];
            }
            
            $phoneDestination = $this->formatPhoneNumber($data['phone']);
            
            // Buat pesan order
            $message = $this->createOrderMessage([
                'order_number' => $data['order_number'],
                'buyer_name' => $data['buyer_name'] ?? 'Pelanggan',
                'total_amount' => $data['total_amount'] ?? 0,
                'shipping_address' => $data['shipping_address'] ?? '-',
                'items' => $data['items'] ?? [],
                'shipping_cost' => $data['shipping_cost'] ?? 0,
                'payment_method' => $data['payment_method'] ?? '-',
                'notes' => $data['notes'] ?? null
            ]);
            
            // Get Fonnte token
            $token = getenv('FONNTE_TOKEN') ?: '49d4wJKAuUKsSqpTrdHU';
            
            // Kirim via Fonnte API
            $result = $this->sendFonnteMessage($token, $phoneDestination, $message);
            
            if ($result['success']) {
                return [
                    'status' => 'success',
                    'message' => 'Notifikasi order berhasil dikirim via Fonnte',
                    'timestamp' => time(),
                    'data' => [
                        'message_id' => $result['message_id'] ?? null,
                        'order_number' => $data['order_number'],
                        'to' => $phoneDestination,
                        'provider' => 'Fonnte'
                    ]
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => $result['error'] ?? 'Gagal mengirim notifikasi',
                    'timestamp' => time()
                ];
            }
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage(),
                'timestamp' => time()
            ];
        }
    }
    
    /**
     * Fonnte Bulk - Kirim ke multiple nomor via Fonnte
     * Method: POST /api/whatsapp/fonnteBulk
     */
    public function fonnteBulk($data = [], $params = []): array
    {
        try {
            // Validasi input
            if (empty($data['recipients']) || !is_array($data['recipients'])) {
                return [
                    'status' => 'error',
                    'message' => 'Recipients wajib diisi dalam format array',
                    'timestamp' => time()
                ];
            }
            
            if (empty($data['message'])) {
                return [
                    'status' => 'error',
                    'message' => 'Pesan (message) wajib diisi',
                    'timestamp' => time()
                ];
            }
            
            $token = getenv('FONNTE_TOKEN') ?: '49d4wJKAuUKsSqpTrdHU';
            $results = [];
            $successCount = 0;
            $failedCount = 0;
            
            // Process setiap recipient
            foreach ($data['recipients'] as $recipient) {
                if (empty($recipient['phone'])) {
                    $failedCount++;
                    $results[] = [
                        'status' => 'failed',
                        'recipient' => $recipient,
                        'message' => 'Nomor telepon tidak valid'
                    ];
                    continue;
                }
                
                try {
                    $phoneDestination = $this->formatPhoneNumber($recipient['phone']);
                    $message = $data['message'];
                    
                    // Personalisasi pesan jika ada nama
                    if (!empty($recipient['name'])) {
                        $message = "Halo {$recipient['name']},\n\n" . $message;
                    }
                    
                    $result = $this->sendFonnteMessage($token, $phoneDestination, $message);
                    
                    if ($result['success']) {
                        $successCount++;
                        $results[] = [
                            'status' => 'success',
                            'recipient' => $recipient,
                            'phone' => $phoneDestination,
                            'message_id' => $result['message_id']
                        ];
                    } else {
                        $failedCount++;
                        $results[] = [
                            'status' => 'failed',
                            'recipient' => $recipient,
                            'error' => $result['error']
                        ];
                    }
                    
                    // Delay 1 detik antar pesan (avoid rate limit)
                    sleep(1);
                    
                } catch (Exception $e) {
                    $failedCount++;
                    $results[] = [
                        'status' => 'failed',
                        'recipient' => $recipient,
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            return [
                'status' => 'success',
                'message' => 'Bulk WhatsApp via Fonnte berhasil diproses',
                'timestamp' => time(),
                'summary' => [
                    'total' => count($data['recipients']),
                    'success' => $successCount,
                    'failed' => $failedCount
                ],
                'results' => $results
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage(),
                'timestamp' => time()
            ];
        }
    }
    
    /**
     * Format nomor telepon ke format WhatsApp (62xxxxxxxxxx)
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Hapus karakter selain angka
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Jika dimulai dengan 0, ganti dengan 62
        if (substr($phone, 0, 1) === '0') {
            $phone = '62' . substr($phone, 1);
        } 
        // Jika tidak dimulai dengan 62, tambahkan 62
        elseif (substr($phone, 0, 2) !== '62') {
            $phone = '62' . $phone;
        }
        
        return $phone;
    }
    
    /**
     * Buat pesan order dengan format yang rapi
     */
    private function createOrderMessage(array $orderData): string
    {
        $message = "📦 *NOTIFIKASI ORDER BARU*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n\n";
        
        // Info order
        $message .= "🔖 *Order:* {$orderData['order_number']}\n";
        $message .= "👤 *Nama:* {$orderData['buyer_name']}\n\n";
        
        // Items (jika ada)
        if (!empty($orderData['items']) && is_array($orderData['items'])) {
            $message .= "📋 *Daftar Produk:*\n";
            foreach ($orderData['items'] as $index => $item) {
                $itemNum = $index + 1;
                $itemName = $item['name'] ?? $item['title'] ?? 'Produk';
                $itemQty = $item['quantity'] ?? 1;
                $itemPrice = $this->formatRupiah($item['price'] ?? 0);
                $message .= "{$itemNum}. {$itemName}\n";
                $message .= "   Qty: {$itemQty} × {$itemPrice}\n";
            }
            $message .= "\n";
        }
        
        // Total
        $message .= "💰 *Detail Pembayaran:*\n";
        $subtotal = $orderData['total_amount'] - ($orderData['shipping_cost'] ?? 0);
        $message .= "Subtotal: " . $this->formatRupiah($subtotal) . "\n";
        
        if (!empty($orderData['shipping_cost']) && $orderData['shipping_cost'] > 0) {
            $message .= "Ongkir: " . $this->formatRupiah($orderData['shipping_cost']) . "\n";
        }
        
        $message .= "*Total: " . $this->formatRupiah($orderData['total_amount']) . "*\n\n";
        
        // Payment method
        if (!empty($orderData['payment_method']) && $orderData['payment_method'] !== '-') {
            $message .= "💳 *Pembayaran:* {$orderData['payment_method']}\n";
        }
        
        // Shipping address
        $message .= "📍 *Alamat Kirim:*\n{$orderData['shipping_address']}\n\n";
        
        // Notes (jika ada)
        if (!empty($orderData['notes'])) {
            $message .= "📝 *Catatan:* {$orderData['notes']}\n\n";
        }
        
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "Terima kasih! 🙏";
        
        return $message;
    }
    
    /**
     * Format angka ke format Rupiah
     */
    private function formatRupiah(float $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
    
    /**
     * Webhook - Terima pesan dari customer
     * Method: POST /api/whatsapp/webhook
     * 
     * Setup:
     * 1. Login fonnte.com
     * 2. Device → Settings → Webhook URL
     * 3. Isi: https://yourdomain.com/api/whatsapp/webhook
     * 4. Save
     * 
     * Customer kirim pesan → Fonnte → Webhook ini
     */
    public function webhook($data = [], $params = []): array
    {
        try {
            // Terima raw data dari Fonnte
            $input = file_get_contents('php://input');
            $webhook = json_decode($input, true);
            
            // Log untuk debug
            error_log('Fonnte Webhook: ' . $input);
            
            // Extract data
            $sender = $webhook['sender'] ?? null;      // 628xxx
            $message = $webhook['message'] ?? null;    // Isi pesan
            $messageType = $webhook['type'] ?? 'text'; // text, image, location
            $device = $webhook['device'] ?? null;      // Nomor bisnis
            
            if (!$sender) {
                return [
                    'status' => 'error',
                    'message' => 'Invalid webhook data'
                ];
            }
            
            // Format nomor
            $phone = $this->formatPhoneNumber($sender);
            
            // Handle berdasarkan type
            switch ($messageType) {
                case 'text':
                    $this->handleTextMessage($phone, $message, $webhook);
                    break;
                    
                case 'image':
                    $imageUrl = $webhook['url'] ?? null;
                    $this->handleImageMessage($phone, $imageUrl);
                    break;
                    
                case 'location':
                    $lat = $webhook['latitude'] ?? null;
                    $lng = $webhook['longitude'] ?? null;
                    $this->handleLocationMessage($phone, $lat, $lng);
                    break;
            }
            
            // Simpan ke log atau database
            $this->saveIncomingMessage($webhook);
            
            // Response success ke Fonnte
            return [
                'status' => 'ok',
                'message' => 'Webhook received'
            ];
            
        } catch (Exception $e) {
            error_log('Webhook Error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Handle pesan text - Auto-reply berdasarkan keyword
     */
    private function handleTextMessage(string $phone, string $message, array $webhook): void
    {
        $lowerMessage = strtolower(trim($message));
        
        // Auto-reply greeting
        if (strpos($lowerMessage, 'halo') !== false || 
            strpos($lowerMessage, 'hai') !== false || 
            strpos($lowerMessage, 'hello') !== false) {
            
            $this->fonnte([
                'phone' => $phone,
                'message' => "Halo! 👋\n\nSelamat datang di layanan kami.\n\nAda yang bisa kami bantu?\n\nBalas dengan:\n• *INFO* - Info produk\n• *ORDER* - Cara order\n• *TRACK* - Lacak pesanan\n• *CS* - Hubungi customer service"
            ], []);
            
        } 
        // Info produk
        elseif (strpos($lowerMessage, 'info') !== false) {
            $this->fonnte([
                'phone' => $phone,
                'message' => "📦 *Info Produk*\n\nLihat katalog lengkap kami di:\n" . $this->url('/products') . "\n\nAtau hubungi CS:\n📱 0812-3456-7890"
            ], []);
            
        }
        // Cara order
        elseif (strpos($lowerMessage, 'order') !== false) {
            $this->fonnte([
                'phone' => $phone,
                'message' => "🛒 *Cara Order:*\n\n1️⃣ Pilih produk di website\n2️⃣ Tambah ke keranjang\n3️⃣ Checkout\n4️⃣ Konfirmasi pembayaran\n\nMudah kan? 😊\n\nLink: " . $this->url('/')
            ], []);
            
        }
        // Track order
        elseif (strpos($lowerMessage, 'track') !== false) {
            // Extract order number jika ada (ORD-xxx)
            preg_match('/ORD-[\d-]+/i', $message, $matches);
            $orderNumber = $matches[0] ?? null;
            
            if ($orderNumber) {
                // Cek order di database
                $order = $this->Storage('orders')
                    ->where('order_number', $orderNumber)
                    ->first();
                
                if ($order) {
                    $status = $order['status'] ?? 'Unknown';
                    $this->fonnte([
                        'phone' => $phone,
                        'message' => "📦 *Status Pesanan*\n\nOrder: {$orderNumber}\nStatus: *{$status}*\n\nTerima kasih! 🙏"
                    ], []);
                } else {
                    $this->fonnte([
                        'phone' => $phone,
                        'message' => "❌ Order {$orderNumber} tidak ditemukan.\n\nPastikan nomor order sudah benar."
                    ], []);
                }
            } else {
                $this->fonnte([
                    'phone' => $phone,
                    'message' => "🔍 *Lacak Pesanan*\n\nKirim nomor order Anda.\nContoh: TRACK ORD-1-12345"
                ], []);
            }
        }
        // Default - notif admin
        else {
            // Log pesan untuk admin review
            error_log("New message from {$phone}: {$message}");
            
            // Optional: Notifikasi ke admin
            // $this->notifyAdmin($phone, $message);
        }
    }
    
    /**
     * Handle pesan gambar
     */
    private function handleImageMessage(string $phone, ?string $imageUrl): void
    {
        error_log("Image received from {$phone}: {$imageUrl}");
        
        // Auto-reply
        $this->fonnte([
            'phone' => $phone,
            'message' => "✅ Gambar Anda telah diterima!\n\nTim kami akan segera menghubungi Anda.\n\nTerima kasih! 🙏"
        ], []);
    }
    
    /**
     * Handle pesan lokasi
     */
    private function handleLocationMessage(string $phone, ?string $lat, ?string $lng): void
    {
        if ($lat && $lng) {
            $mapsUrl = "https://www.google.com/maps?q={$lat},{$lng}";
            error_log("Location from {$phone}: {$mapsUrl}");
            
            // Simpan lokasi customer (optional)
            /* Uncomment jika perlu simpan
            $this->Storage('customer_locations')->insert([
                'phone' => $phone,
                'latitude' => $lat,
                'longitude' => $lng,
                'received_at' => date('Y-m-d H:i:s')
            ]);
            */
            
            // Auto-reply
            $this->fonnte([
                'phone' => $phone,
                'message' => "📍 *Lokasi Diterima!*\n\nKoordinat: {$lat}, {$lng}\n\nMaps: {$mapsUrl}\n\nTerima kasih! 🙏"
            ], []);
        }
    }
    
    /**
     * Simpan pesan masuk ke log file
     */
    private function saveIncomingMessage(array $webhook): void
    {
        try {
            // Log ke file
            $logFile = __DIR__ . '/../../system/log/webhook_' . date('Y-m-d') . '.log';
            $logData = date('Y-m-d H:i:s') . ' - ' . json_encode($webhook) . "\n";
            file_put_contents($logFile, $logData, FILE_APPEND);
            
            // Optional: Simpan ke database
            /* Uncomment jika ada table
            $this->Storage('incoming_messages')->insert([
                'sender' => $webhook['sender'] ?? null,
                'message' => $webhook['message'] ?? null,
                'type' => $webhook['type'] ?? 'text',
                'url' => $webhook['url'] ?? null,
                'device' => $webhook['device'] ?? null,
                'webhook_data' => json_encode($webhook),
                'received_at' => date('Y-m-d H:i:s')
            ]);
            */
        } catch (Exception $e) {
            error_log('Save message error: ' . $e->getMessage());
        }
    }
    
    /**
     * Notifikasi admin ada pesan baru (optional)
     */
    private function notifyAdmin(string $sender, string $message): void
    {
        // Kirim notif ke nomor admin
        $adminPhone = '6281234567890'; // Ganti dengan nomor admin Anda
        
        $notif = "🔔 *Pesan Baru dari Customer*\n\n";
        $notif .= "Dari: {$sender}\n";
        $notif .= "Pesan: {$message}\n\n";
        $notif .= "Balas langsung dari WhatsApp Anda.";
        
        // Uncomment untuk aktifkan notif admin
        /*
        $this->fonnte([
            'phone' => $adminPhone,
            'message' => $notif
        ], []);
        */
    }
    
    /**
     * Send message via Fonnte API
     */
    private function sendFonnteMessage(string $token, string $phone, string $message): array
    {
        $url = "https://api.fonnte.com/send";
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [
                'target' => $phone,
                'message' => $message,
                'countryCode' => '62' // Indonesia
            ],
            CURLOPT_HTTPHEADER => [
                "Authorization: $token"
            ],
            CURLOPT_TIMEOUT => 30,
            // Fix SSL certificate error di Windows
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        
        // Handle curl error
        if ($error) {
            return [
                'success' => false,
                'error' => 'CURL Error: ' . $error
            ];
        }
        
        // Parse response
        $decoded = json_decode($response, true);
        
        // Check for API error
        if ($httpCode !== 200) {
            return [
                'success' => false,
                'error' => "API Error (HTTP {$httpCode})",
                'details' => $decoded
            ];
        }
        
        // Check Fonnte response status
        if (!isset($decoded['status']) || !$decoded['status']) {
            return [
                'success' => false,
                'error' => $decoded['reason'] ?? 'Unknown error from Fonnte',
                'details' => $decoded
            ];
        }
        
        // Success
        return [
            'success' => true,
            'message_id' => $decoded['id'] ?? $decoded['detail']['id'] ?? null,
            'response' => $decoded
        ];
    }
}
