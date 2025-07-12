<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");

require_once 'config.php';
require_once 'conversation.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['audio'])) {
    $audioFile = $_FILES['audio'];
    $deepgramApiKey = DEEPGRAM_API_KEY;
    $deepgramApiUrl = 'https://api.deepgram.com/v1/listen';

    $postData = file_get_contents($audioFile['tmp_name']);

    $options = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: audio/webm\r\n" .
                        "Authorization: Token " . $deepgramApiKey,
            'content' => $postData,
        ],
    ];

    $context = stream_context_create($options);
    $response = file_get_contents($deepgramApiUrl, false, $context);
    $responseData = json_decode($response, true);

    if (isset($responseData['results']['channels'][0]['alternatives'][0]['transcript'])) {
        $transcript = $responseData['results']['channels'][0]['alternatives'][0]['transcript'];
        process_user_response($transcript);
        $next_question = get_next_question();

        if ($next_question === null) {
            $properties = search_properties();
            echo json_encode(['success' => true, 'properties' => $properties, 'transcript' => $transcript]);
        } else {
            echo json_encode(['success' => true, 'message' => $next_question, 'transcript' => $transcript]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Transcription failed.', 'transcript' => '']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No audio file received.', 'transcript' => '']);
}
?>
