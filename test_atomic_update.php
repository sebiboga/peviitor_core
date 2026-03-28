<?php

$solrUrl = 'https://solr.peviitor.ro/solr/company';
$auth = 'solr:SolrRocks';

function solrRequest($url, $method, $data = null) {
    global $auth;
    
    $context = stream_context_create([
        'http' => [
            'method' => $method,
            'header' => [
                "Authorization: Basic " . base64_encode($auth),
                "Content-Type: application/json"
            ],
            'content' => $data,
            'ignore_errors' => true
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);
    
    $response = file_get_contents($url, false, $context);
    $httpCode = 200;
    
    if (isset($http_response_header[0])) {
        preg_match('/\d{3}/', $http_response_header[0], $matches);
        $httpCode = isset($matches[0]) ? (int)$matches[0] : 200;
    }
    
    return ['code' => $httpCode, 'response' => json_decode($response, true)];
}

$testDoc = [
    [
        'id' => '99999999',
        'company' => ['set' => 'TEST COMPANY SRL - ATOMIC UPDATE'],
        'status' => ['set' => 'activ'],
        'location' => ['set' => 'Bucuresti']
    ]
];

echo "Testing atomic insert...\n";
echo "Document: " . json_encode($testDoc[0], JSON_PRETTY_PRINT) . "\n\n";

$result = solrRequest($solrUrl . '/update?commit=true', 'POST', json_encode($testDoc));

echo "HTTP Code: " . $result['code'] . "\n";
echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT) . "\n\n";

if ($result['code'] === 200) {
    echo "SUCCESS! Now querying to verify...\n\n";
    
    $queryResult = solrRequest($solrUrl . '/select?q=id:99999999', 'GET');
    echo "Query result:\n";
    echo json_encode($queryResult['response'], JSON_PRETTY_PRINT);
} else {
    echo "FAILED!\n";
}
