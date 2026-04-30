<?php
// No session check — Stripe webhooks authenticate via signature verification inside the handler
require_once '/opt/mka/api/stripe/webhook.php';