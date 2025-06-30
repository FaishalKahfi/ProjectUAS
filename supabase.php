<?php
define('SUPABASE_URL', 'https://oedivlvixbbovizhavwa.supabase.co');
define('SUPABASE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Im9lZGl2bHZpeGJib3ZpemhhdndhIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTEyNTgzMDcsImV4cCI6MjA2NjgzNDMwN30.XpV1qEVjwqfepbC6AcBVkO-u-9Mf1ttszf4pOYZ11Ic');

// Helper request ke Supabase REST
function supabase_request($method, $endpoint, $data = null) {
    $url = SUPABASE_URL . $endpoint;
    $headers = [
        "apikey: " . SUPABASE_KEY,
        "Authorization: Bearer " . SUPABASE_KEY,
        "Content-Type: application/json",
        "Prefer: return=representation"
    ];
    $opts = [
        'http' => [
            'method' => $method,
            'header' => implode("\r\n", $headers),
        ]
    ];
    if ($data !== null) {
        $opts['http']['content'] = json_encode($data);
    }
    $context = stream_context_create($opts);
    $res = file_get_contents($url, false, $context);
    return json_decode($res, true);
}
?>
