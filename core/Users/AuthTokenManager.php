<?php

namespace MKA\Users;

use PDO;

class AuthTokenManager
{
    protected $UserUUID;
    protected $db;
    
    
    public function __construct()
    {
        global $pdo;
        $this->db = $pdo;
    }
    
    public function validate($token)
    {
        
        $stmt = $this->db->prepare("SELECT * FROM mka_user_auth_tokens WHERE token = ? AND expires_at > NOW()");
        $stmt->execute([$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($userUUID)
    {
        
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+7 days'));

        
        $stmt = $this->db->prepare("INSERT INTO mka_user_auth_tokens (token, user_uuid, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$token, $userUUID, $expires]);

        return $token;
    }
}
