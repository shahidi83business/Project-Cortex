<?php
require_once 'config.php';

function sendMessage($chatId, $text)
{
    $url = "https://api.telegram.org/bot".BOT_TOKEN."/sendMessage";

    $payload = [
        'chat_id' => $chatId,
        'text' => $text
    ];

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => $payload
    ]);

    curl_exec($ch);
    curl_close($ch);
}
