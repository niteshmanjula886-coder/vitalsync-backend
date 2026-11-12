<?php

if (!function_exists("resolveAvatarUrl")) {
    /**
     * Converts a relative avatar path (e.g. uploads/avatars/avatar_10_1731420000.jpg)
     * into a full HTTP/HTTPS URL using the current request host, and normalizes
     * previously stored full URLs under /vitalsync-backend/ so LAN IP changes
     * never break saved profile pictures.
     */
    function resolveAvatarUrl($relativePath) {
        if (empty($relativePath)) {
            return null;
        }
        $trimmed = trim((string)$relativePath);
        if ($trimmed === "") {
            return null;
        }
        if (preg_match('#^https?://[^/]+/(?:vitalsync-backend|vitalsync_background)/(uploads/.+)$#i', $trimmed, $m)) {
            $trimmed = $m[1];
        } elseif (preg_match('#^https?://#i', $trimmed)) {
            return $trimmed;
        }
        $protocol = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https://" : "http://";
        $host = $_SERVER["HTTP_HOST"] ?? "localhost";
        $baseUrl = $protocol . $host . "/vitalsync-backend/";
        return $baseUrl . ltrim($trimmed, "/");
    }
}

