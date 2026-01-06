<?php
namespace MKA\Testimonials;

class TestimonialManager {
    protected $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    protected function generate_uuid() {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    public function addTestimonial($data) {
        $uuid = $this->generate_uuid();
        $stmt = $this->pdo->prepare("
            INSERT INTO mka_testimonials
            (testimonial_id, user_name, user_email, testimonial_text, product_id, service_id, submitted_at)
            VALUES (:testimonial_id, :user_name, :user_email, :testimonial_text, :product_id, :service_id, NOW())
        ");
        $stmt->execute([
            ':testimonial_id' => $uuid,
            ':user_name' => $data['user_name'],
            ':user_email' => $data['user_email'],
            ':testimonial_text' => $data['testimonial_text'],
            ':product_id' => $data['product_id'] ?? null,
            ':service_id' => $data['service_id'] ?? null
        ]);
        return $uuid;
    }

    public function getTestimonials($status = 'pending') {
        $stmt = $this->pdo->prepare("
            SELECT * FROM mka_testimonials
            WHERE status = :status
            ORDER BY submitted_at DESC
        ");
        $stmt->execute([':status' => $status]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTestimonialById($testimonial_id) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM mka_testimonials WHERE testimonial_id = :testimonial_id
        ");
        $stmt->execute([':testimonial_id' => $testimonial_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function approveTestimonial($testimonial_id, $admin_user_id, $reward_type = null, $reward_value = null) {
        $stmt = $this->pdo->prepare("
            UPDATE mka_testimonials
            SET status = 'approved',
                approved_at = NOW(),
                approved_by = :approved_by,
                reward_type = :reward_type,
                reward_value = :reward_value
            WHERE testimonial_id = :testimonial_id
        ");
        $stmt->execute([
            ':approved_by' => $admin_user_id,
            ':reward_type' => $reward_type,
            ':reward_value' => $reward_value,
            ':testimonial_id' => $testimonial_id
        ]);
        return $stmt->rowCount();
    }

    public function rejectTestimonial($testimonial_id, $admin_user_id) {
        $stmt = $this->pdo->prepare("
            UPDATE mka_testimonials
            SET status = 'rejected',
                approved_at = NOW(),
                approved_by = :approved_by
            WHERE testimonial_id = :testimonial_id
        ");
        $stmt->execute([
            ':approved_by' => $admin_user_id,
            ':testimonial_id' => $testimonial_id
        ]);
        return $stmt->rowCount();
    }

    public function markRewardSent($testimonial_id) {
        $stmt = $this->pdo->prepare("
            UPDATE mka_testimonials
            SET reward_sent_at = NOW()
            WHERE testimonial_id = :testimonial_id
        ");
        $stmt->execute([':testimonial_id' => $testimonial_id]);
        return $stmt->rowCount();
    }

    public function deleteTestimonial($testimonial_id) {
        $stmt = $this->pdo->prepare("
            DELETE FROM mka_testimonials WHERE testimonial_id = :testimonial_id
        ");
        $stmt->execute([':testimonial_id' => $testimonial_id]);
        return $stmt->rowCount();
    }
}

