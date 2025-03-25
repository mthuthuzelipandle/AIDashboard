<?php
require_once 'config.php';

function testAssistantConfig() {
    echo "Testing OpenAI Assistant configuration...\n";
    
    // Check API key
    if (empty(OPENAI_API_KEY)) {
        echo "❌ OpenAI API key is not set\n";
        return false;
    }
    echo "✓ OpenAI API key is set (length: " . strlen(OPENAI_API_KEY) . ")\n";
    
    // Check Assistant ID
    if (empty(OPENAI_ASSISTANT_ID)) {
        echo "❌ OpenAI Assistant ID is not set\n";
        return false;
    }
    echo "✓ OpenAI Assistant ID is set\n";
    
    // Test Assistant API access
    $ch = curl_init('https://api.openai.com/v1/assistants/' . OPENAI_ASSISTANT_ID);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . OPENAI_API_KEY,
            'OpenAI-Beta: assistants=v2'
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $assistant = json_decode($response, true);
        echo "✓ Successfully connected to Assistant\n";
        echo "Assistant name: " . ($assistant['name'] ?? 'Unnamed') . "\n";
        echo "Assistant model: " . ($assistant['model'] ?? 'Unknown') . "\n";
        return true;
    } else {
        echo "❌ Failed to connect to Assistant (HTTP $httpCode)\n";
        echo "Error: " . $response . "\n";
        return false;
    }
}

testAssistantConfig();
