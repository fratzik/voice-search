<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['audio'])) {
    $audioFile = $_FILES['audio'];

    require_once 'config.php';
    $deepgramApiKey = DEEPGRAM_API_KEY;

    // Deepgram API URL
    $deepgramApiUrl = 'https://api.deepgram.com/v1/listen';

    // Prepare the request data
    $postData = file_get_contents($audioFile['tmp_name']);

    // Set up the HTTP request headers
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: audio/webm\r\n" .
                        "Authorization: Token " . $deepgramApiKey,
            'content' => $postData,
        ],
    ];

    // Create the stream context
    $context = stream_context_create($options);

    // Send the request to Deepgram
    $response = file_get_contents($deepgramApiUrl, false, $context);

    // Decode the response
    $responseData = json_decode($response, true);

    // Check for transcription
    if (isset($responseData['results']['channels'][0]['alternatives'][0]['transcript'])) {
        $transcript = $responseData['results']['channels'][0]['alternatives'][0]['transcript'];

        // Check for real estate keywords
        $keywords = ['house', 'apartment', 'rent', 'buy', 'property'];
        $foundKeywords = [];
        foreach ($keywords as $keyword) {
            if (stripos($transcript, $keyword) !== false) {
                $foundKeywords[] = $keyword;
            }
        }

        if (count($foundKeywords) > 0) {
            // Keywords found, generate a response
            $responseText = 'I found results for your search about ' . implode(', ', $foundKeywords) . '.';

            // For now, we'll just send back a success message.
            // In the next step, we'll integrate with Deepgram's TTS.
            echo json_encode(['success' => true, 'message' => $responseText]);

        } else {
            // No keywords found
            echo json_encode(['success' => false, 'message' => 'I could not identify a search query in your speech.']);
        }
    } else {
        // Transcription failed
        echo json_encode(['success' => false, 'message' => 'Transcription failed.']);
    }
} else {
    // No audio file received
    echo json_encode(['success' => false, 'message' => 'No audio file received.']);
}
?>
