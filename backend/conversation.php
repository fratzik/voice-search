<?php
session_start();

function get_next_question() {
    if (!isset($_SESSION['conversation_state'])) {
        $_SESSION['conversation_state'] = [];
        return "Welcome to FGRealty! Are you looking to rent or buy a property?";
    }

    $state = $_SESSION['conversation_state'];

    if (!isset($state['operation_type'])) {
        return "Great! What type of property are you interested in? A villa, apartment, or townhouse?";
    }

    if (!isset($state['property_type'])) {
        return "Okay, and where are you looking for this property?";
    }

    if (!isset($state['location'])) {
        return "Perfect. What is your approximate budget for this?";
    }

    if (!isset($state['budget'])) {
        return "Thank you. I will now search for properties that match your criteria.";
    }

    return null; // All information collected
}

function process_user_response($transcript) {
    $state = &$_SESSION['conversation_state'];

    if (!isset($state['operation_type'])) {
        if (stripos($transcript, 'rent') !== false) {
            $state['operation_type'] = 'rent';
        } elseif (stripos($transcript, 'buy') !== false || stripos($transcript, 'sale') !== false) {
            $state['operation_type'] = 'sale';
        }
        return;
    }

    if (!isset($state['property_type'])) {
        if (stripos($transcript, 'villa') !== false) {
            $state['property_type'] = 'villa';
        } elseif (stripos($transcript, 'apartment') !== false) {
            $state['property_type'] = 'apartment';
        } elseif (stripos($transcript, 'townhouse') !== false) {
            $state['property_type'] = 'townhouse';
        }
        return;
    }

    if (!isset($state['location'])) {
        $state['location'] = $transcript;
        return;
    }

    if (!isset($state['budget'])) {
        $state['budget'] = $transcript;
        return;
    }
}

function search_properties() {
    $state = $_SESSION['conversation_state'];
    $url = 'https://www.fgrealty.qa/api/properties?';

    // Extract budget from and to
    $budget = $state['budget'];
    $pf = null;
    $pt = null;
    if (preg_match('/(\d+)\s*to\s*(\d+)/', $budget, $matches)) {
        $pf = $matches[1];
        $pt = $matches[2];
    } elseif (preg_match('/(?:under|less\s*than)\s*(\d+)/', $budget, $matches)) {
        $pt = $matches[1];
    } elseif (preg_match('/(?:over|more\s*than)\s*(\d+)/', $budget, $matches)) {
        $pf = $matches[1];
    } elseif (preg_match('/(\d+)/', $budget, $matches)) {
        $pf = $matches[1] - 500;
        $pt = $matches[1] + 500;
    }

    $params = [
        'ot' => $state['operation_type'],
        't' => $state['property_type'],
        'loc' => $state['location'],
        'pf' => $pf,
        'pt' => $pt,
        'limit' => 6,
    ];
    $url .= http_build_query($params);

    $response = file_get_contents($url);
    return json_decode($response, true);
}
?>
