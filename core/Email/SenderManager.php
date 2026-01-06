<?php

namespace MKA\Email;




class SenderManager
{
    protected $UserUUID;
    protected $db;

    public function __construct($UserUUID)
    {
        global $pdo;
        $this->UserUUID = $UserUUID;
        $this->db = $pdo;
    }

    public function getAllSenders()
    {
        $stmt = $this->db->prepare("SELECT * FROM mka_senders WHERE UserUUID = ? AND Active = 'y'");
        $stmt->execute([$this->UserUUID]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createSender($data)
    {
        $uuid = $this->generateUUID();
        $stmt = $this->db->prepare("
            INSERT INTO mka_senders
            (SenderUUID, UserUUID, Email, Provider, DisplayName, AuthType, AuthToken, RefreshToken, SMTPServer, SMTPPort, UseTLS)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $uuid,
            $this->UserUUID,
            $data['Email'],
            $data['Provider'],
            $data['DisplayName'] ?? '',
            $data['AuthType'] ?? 'oauth',
            $data['AuthToken'] ?? null,
            $data['RefreshToken'] ?? null,
            $data['SMTPServer'] ?? '',
            $data['SMTPPort'] ?? 587,
            $data['UseTLS'] ?? 'y'
        ]);

        return ['success' => true, 'SenderUUID' => $uuid];
    }

    public function deleteSender($SenderUUID)
    {
        $stmt = $this->db->prepare("UPDATE mka_senders SET Active = 'n' WHERE SenderUUID = ? AND UserUUID = ?");
        $stmt->execute([$SenderUUID, $this->UserUUID]);
        return ['success' => true];
    }

    protected function generateUUID()
    {
        return bin2hex(random_bytes(16));
    }
    protected function refreshExchangeToken($sender) {
        $config = require '/opt/mka/config/exchange.php';

        // Make a POST request to Microsoft token endpoint
        $response = file_get_contents('https://login.microsoftonline.com/' . $config['tenant'] . '/oauth2/v2.0/token', false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query([
                    'client_id' => $config['client_id'],
                    'client_secret' => $config['client_secret'],
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $sender['RefreshToken'],
                    'scope' => 'openid offline_access SMTP.Send email',
                ]),
            ]
        ]));

        $data = json_decode($response, true);

        if (!isset($data['access_token'])) {
            throw new \Exception('Failed to refresh Exchange access token.');
        }

        $newAccessToken = $data['access_token'];
        $newRefreshToken = $data['refresh_token'] ?? $sender['RefreshToken']; // Microsoft sometimes issues a new refresh token, sometimes not

        // Update database
        $db = $GLOBALS['pdo'];
        $stmt = $db->prepare("UPDATE mka_senders SET AuthToken = ?, RefreshToken = ?, Updated = NOW() WHERE SenderUUID = ?");
        $stmt->execute([
            $newAccessToken,
            $newRefreshToken,
            $sender['SenderUUID']
        ]);

        // Return the new token so the caller can continue sending immediately
        return $newAccessToken;
    }

}

