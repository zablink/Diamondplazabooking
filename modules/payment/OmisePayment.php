<?php
/**
 * Omise Payment Gateway Integration
 * รองรับการชำระเงินผ่าน Omise
 */

class OmisePayment {
    private $publicKey;
    private $secretKey;
    private $apiVersion = '2019-05-29';
    private $apiUrl = 'https://api.omise.co/';
    
    public function __construct() {
        $this->publicKey = PAYMENT_PUBLIC_KEY;
        $this->secretKey = PAYMENT_SECRET_KEY;
    }
    
    /**
     * สร้าง Charge (การชำระเงิน)
     */
    public function createCharge($amount, $currency, $token, $description, $metadata = []) {
        try {
            $url = $this->apiUrl . 'charges';
            
            $data = [
                'amount' => $amount, // จำนวนเงินในหน่วยสตางค์ (บาท * 100)
                'currency' => $currency, // THB
                'card' => $token, // Omise Token จาก Omise.js
                'description' => $description,
                'metadata' => $metadata,
                'return_uri' => SITE_URL . '/payment-callback.php'
            ];
            
            $response = $this->makeRequest('POST', $url, $data);
            
            return $response;
            
        } catch (Exception $e) {
            error_log("Omise Create Charge Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการสร้างรายการชำระเงิน: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * ตรวจสอบสถานะการชำระเงิน
     */
    public function getCharge($chargeId) {
        try {
            $url = $this->apiUrl . 'charges/' . $chargeId;
            $response = $this->makeRequest('GET', $url);
            
            return $response;
            
        } catch (Exception $e) {
            error_log("Omise Get Charge Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการตรวจสอบสถานะ: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * สร้าง Customer
     */
    public function createCustomer($email, $description, $metadata = []) {
        try {
            $url = $this->apiUrl . 'customers';
            
            $data = [
                'email' => $email,
                'description' => $description,
                'metadata' => $metadata
            ];
            
            $response = $this->makeRequest('POST', $url, $data);
            
            return $response;
            
        } catch (Exception $e) {
            error_log("Omise Create Customer Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการสร้างลูกค้า: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Refund การชำระเงิน
     */
    public function refund($chargeId, $amount = null) {
        try {
            $url = $this->apiUrl . 'charges/' . $chargeId . '/refunds';
            
            $data = [];
            if ($amount !== null) {
                $data['amount'] = $amount;
            }
            
            $response = $this->makeRequest('POST', $url, $data);
            
            return $response;
            
        } catch (Exception $e) {
            error_log("Omise Refund Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการคืนเงิน: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * ทำ HTTP Request ไปยัง Omise API
     */
    private function makeRequest($method, $url, $data = []) {
        $ch = curl_init($url);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->secretKey . ':');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Omise-Version: ' . $this->apiVersion
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'GET') {
            if (!empty($data)) {
                $url .= '?' . http_build_query($data);
                curl_setopt($ch, CURLOPT_URL, $url);
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            throw new Exception('CURL Error: ' . $error);
        }
        
        $responseData = json_decode($response, true);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'success' => true,
                'data' => $responseData
            ];
        } else {
            $errorMessage = $responseData['message'] ?? 'Unknown error';
            throw new Exception('API Error: ' . $errorMessage);
        }
    }
    
    /**
     * ตรวจสอบ Webhook signature
     */
    public function verifyWebhook($payload, $signature) {
        // Omise ใช้ HMAC-SHA256 สำหรับ verify webhook
        $expectedSignature = hash_hmac('sha256', $payload, $this->secretKey);
        return hash_equals($expectedSignature, $signature);
    }
    
    /**
     * แปลงสถานะจาก Omise เป็นสถานะภายในระบบ
     */
    public function translateStatus($omiseStatus) {
        $statusMap = [
            'successful' => 'confirmed',
            'failed' => 'cancelled',
            'pending' => 'pending',
            'reversed' => 'cancelled',
            'expired' => 'cancelled'
        ];
        
        return $statusMap[$omiseStatus] ?? 'pending';
    }
    
    /**
     * Get Public Key
     */
    public function getPublicKey() {
        return $this->publicKey;
    }
}
