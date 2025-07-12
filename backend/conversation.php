<?php
session_start();

function get_next_question() {
    if (!isset($_SESSION['conversation_state'])) {
        $_SESSION['conversation_state'] = [];
        return "Hello! I'm your real estate assistant. Are you looking to buy or rent?";
    }

    $state = $_SESSION['conversation_state'];

    if (!isset($state['operation_type'])) {
        return "Are you looking for a house or an apartment?";
    }

    if (!isset($state['property_type'])) {
        return "What is your desired location?";
    }

    if (!isset($state['location'])) {
        return "What is your budget?";
    }

    if (!isset($state['budget'])) {
        return "I have all the information I need. I will now search for properties for you.";
    }

    return null; // All information collected
}

function process_user_response($transcript) {
    $state = &$_SESSION['conversation_state'];

    if (!isset($state['operation_type'])) {
        if (stripos($transcript, 'buy') !== false) {
            $state['operation_type'] = 'buy';
        } elseif (stripos($transcript, 'rent') !== false) {
            $state['operation_type'] = 'rent';
        }
        return;
    }

    if (!isset($state['property_type'])) {
        if (stripos($transcript, 'house') !== false) {
            $state['property_type'] = 'house';
        } elseif (stripos($transcript, 'apartment') !== false) {
            $state['property_type'] = 'apartment';
        }
        return;
    }

    if (!isset($state['location'])) {
        // For simplicity, we'll just take the whole transcript as the location.
        $state['location'] = $transcript;
        return;
    }

    if (!isset($state['budget'])) {
        // For simplicity, we'll just take the whole transcript as the budget.
        $state['budget'] = $transcript;
        return;
    }
}

function search_properties() {
    $state = $_SESSION['conversation_state'];
    $url = 'https://www.fgrealty.qa/api/properties?';
    $params = [
        'operation_type' => $state['operation_type'],
        'property_type' => $state['property_type'],
        'location' => $state['location'],
        'budget' => $state['budget'],
        'limit' => 6,
    ];
    $url .= http_build_query($params);

    $response = file_get_contents($url);
    return json_decode($response, true);
}
?>
