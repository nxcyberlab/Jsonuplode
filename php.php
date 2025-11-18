
<?php
// ==========================
// CONFIGURATION
// ==========================
$cpanel_json_url = 'https://imranspict.hstn.me/users.json';
$github_user = 'nxcyberlab';
$github_repo = 'Jsonuplode';
$github_branch = 'main';
$github_token = getenv('GH_TOKEN'); // GitHub Actions automatically provides this token

$github_file_path = 'users.json'; // Repo তে কোন ফাইল আপডেট হবে

// ==========================
// 1. Fetch cPanel JSON
// ==========================
$cpanel_json = file_get_contents($cpanel_json_url);
if (!$cpanel_json) {
    echo "Failed to fetch cPanel JSON.\n";
    exit(1);
}

// Decode JSON
$data = json_decode($cpanel_json, true);
if (!$data) {
    echo "Invalid JSON format.\n";
    exit(1);
}

// ==========================
// 2. Extract device IDs
// ==========================
$devices = array();
foreach ($data as $item) {
    if (isset($item['device'])) {
        // Clean device ID (remove extra spaces)
        $devices[] = trim($item['device']);
    }
}

// Prepare new JSON
$new_json = json_encode(array('devices' => $devices), JSON_PRETTY_PRINT);

// ==========================
// 3. Push to GitHub via API
// ==========================
$api_url = "https://api.github.com/repos/$github_user/$github_repo/contents/$github_file_path";

// First, get the SHA of existing file (required by GitHub API)
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url . '?ref=' . $github_branch);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    "Authorization: token $github_token",
    "User-Agent: PHP"
));
$response = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);

if (!$response) {
    echo "Failed to get GitHub file info.\n";
    exit(1);
}

$response_data = json_decode($response, true);
$sha = isset($response_data['sha']) ? $response_data['sha'] : null;

// Prepare payload
$payload = json_encode(array(
    'message' => 'Auto update device list',
    'content' => base64_encode($new_json),
    'branch' => $github_branch,
    'sha' => $sha
));

// Push via API
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
