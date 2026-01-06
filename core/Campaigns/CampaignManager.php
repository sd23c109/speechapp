<?php


namespace MKA\Campaigns;




class CampaignManager
{
    protected $UserUUID;
    protected $db;

    public function __construct($UserUUID)
    {
        global $pdo;
        $this->UserUUID = $UserUUID;
        $this->db = $pdo;
    }

    public function getAllCampaigns()
    {
        $stmt = $this->db->prepare("SELECT * FROM mka_campaigns WHERE UserUUID = ? AND Deleted = 'n' ORDER BY Created DESC");
        $stmt->execute([$this->UserUUID]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCampaignByUUID($CampaignUUID)
    {
        $stmt = $this->db->prepare("SELECT * FROM mka_campaigns WHERE CampaignUUID = ? AND UserUUID = ? AND Deleted = 'n'");
        $stmt->execute([$CampaignUUID, $this->UserUUID]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createCampaign(array $data)
    {
        $uuid = $this->generateUUID();
        $now = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare("
            INSERT INTO mka_campaigns (CampaignUUID, UserUUID, Title, Description, Status, Created, Updated)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $uuid,
            $this->UserUUID,
            $data['Title'] ?? 'Untitled',
            $data['Description'] ?? '',
            $data['Status'] ?? 'draft',
            $now,
            $now
        ]);

        return ['success' => true, 'CampaignUUID' => $uuid];
    }

    public function updateCampaign(array $data)
    {
        if (empty($data['CampaignUUID'])) {
            return ['success' => false, 'error' => 'Missing CampaignUUID'];
        }

        $stmt = $this->db->prepare("
            UPDATE mka_campaigns
            SET Title = ?, Description = ?, Status = ?, Updated = NOW()
            WHERE CampaignUUID = ? AND UserUUID = ? AND Deleted = 'n'
        ");
        $stmt->execute([
            $data['Title'] ?? '',
            $data['Description'] ?? '',
            $data['Status'] ?? 'draft',
            $data['CampaignUUID'],
            $this->UserUUID
        ]);

        return ['success' => true];
    }
    
    public function deleteCampaign($CampaignUUID)
        {
            $stmt = $this->db->prepare("
                UPDATE mka_campaigns
                SET Deleted = 'y', Updated = NOW()
                WHERE CampaignUUID = ? AND UserUUID = ?
            ");
            $stmt->execute([$CampaignUUID, $this->UserUUID]);

            return ['success' => true];
        }


    protected function generateUUID()
    {
        return bin2hex(random_bytes(16));
    }
}
  
?>
