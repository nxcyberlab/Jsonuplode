<?php
$cpanel_json_url = 'https://imranspict.hstn.me/users.json';
$github_user = 'nxcyberlab';
$github_repo = 'Jsonuplode';
$github_branch = 'main';
$github_token = getenv('GH_TOKEN');
$github_file_path = 'users.json';

// Fetch JSON
$cpanel_json = file_get_contents($cpanel_json_url);
if (!$cpanel_json) {
    echo "Failed to fetch JSON.\n";
    exit(1);
}

// Decode JSON
$data = json_decode($cpanel_json, true);
if (!$data || !isset($data['devices'])) {
    echo "Invalid JSON format or 'devices' key missing.\n";
    exit(1);
}

// Use devices directly
$devices = $data['devices'];

// Prepare new JSON
$new_json = json_encode(array('devices' => $devices), JSON_PRETTY_PRINT);

// GitHub API push
$api_url = "https://api.github.com/repos/$github_user/$github_repo/contents/$github_file_path";

// Get SHA
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url . '?ref=' . $github_branch);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    "Authorization: token $github_token",
    "User-Agent: PHP"
));
$response = curl_exec($ch);
curl_close($ch);
$response_data = json_decode($response, true);
$sha = isset($response_data['sha']) ? $response_data['sha'] : null;

// Push new content
$payload = json_encode(array(
    'message' => 'Auto update device list',
    'content' => base64_encode($new_json),
    'branch' => $github_branch,
    'sha' => $sha
));

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    "Authorization: token $github_token",
    "User-Agent: PHP",
    "Content-Type: application/json"
));
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
$result = curl_exec($ch);
curl_close($ch);

echo "GitHub update finished.\n";
?>
