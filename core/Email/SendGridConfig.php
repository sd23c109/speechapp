<?php
namespace MKA\Email;

class SendGridConfig {

    public static function getApiKey() {
        return getenv('SENDGRID_API_KEY');
    }

    public static function getFromEmail() {
        return getenv('SENDGRID_FROM_EMAIL') ?: 'support@virtuops.com';
    }

    public static function getFromName() {
        return getenv('SENDGRID_FROM_NAME') ?: 'Speech App';
    }
}