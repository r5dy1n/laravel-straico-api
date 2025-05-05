<?php

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use r5dy1n\Straico\StraicoService;

// --- Configuration ---
// Load .env file
$dotenv = Dotenv::createImmutable(__DIR__ . '/..'); // Load from project root
$dotenv->safeLoad();

$apiKey = $_ENV['STRAICO_API_KEY'] ?? null;
// Construct base URL carefully, ensuring no double slashes later
$baseUrl = rtrim($_ENV['STRAICO_BASE_URL'] ?? 'https://api.straico.com', '/');
$timeout = (int) ($_ENV['STRAICO_TIMEOUT'] ?? 60);

if (!$apiKey) {
    die("Error: STRAICO_API_KEY not found in .env file.\n");
}
if (!$baseUrl) {
     die("Error: STRAICO_BASE_URL not found in .env file.\n");
}

// --- Service Instantiation ---
// The StraicoService now handles Guzzle client creation internally.
// We pass the configuration directly to its constructor.
$straico = new StraicoService($apiKey, $baseUrl, $timeout);

// --- Helper Function for Output ---
function runTest(string $testName, callable $testFunc)
{
    echo "--- Running Test: {$testName} ---\n";
    try {
        $result = $testFunc();
        echo "Result:\n";
        print_r($result);
        echo "\n--- Test Passed: {$testName} ---\n\n";
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        // Optionally print stack trace: echo $e->getTraceAsString() . "\n";
        echo "--- Test Failed: {$testName} ---\n\n";
    }
}

// --- Test Execution ---

// 1. List Models (GET /v1/models)
// runTest('List Models', function () use ($straico) {
//     // Note: The service method prepends 'v1/' internally now
//     return $straico->listModels();
// });

// 2. Prompt Completion (POST /v1/prompt/completion)
runTest('Prompt Completion', function () use ($straico) {
    // --- !! IMPORTANT !! ---
    // Replace with valid model IDs from your 'listModels' output
    // Replace URLs with accessible ones if needed for testing this specific feature
    $models = ['openai/gpt-4o-mini']; // Example: Use a cheap/fast model for testing
    $message = 'Explain quantum mechanics like i\'m 5';
    // Optional: Add valid file_urls, youtube_urls, images if testing context features
    // $fileUrls = ['https://example.com/some_document.pdf'];
    // $youtubeUrls = ['https://www.youtube.com/watch?v=dQw4w9WgXcQ']; // Example
    // $imageUrls = ['https://example.com/some_image.jpg'];

    $params = [
        'models' => $models,
        'message' => $message,
        // 'file_urls' => $fileUrls,
        // 'youtube_urls' => $youtubeUrls,
        // 'images' => $imageUrls,
    ];
    // Note: The service method prepends 'v1/' internally now
    return $straico->createPromptCompletion($params);
});

// // 3. Upload File (POST /v0/file/upload)
// runTest('Upload File', function () use ($straico) {
//     // --- !! IMPORTANT !! ---
//     // Create a dummy file for testing or use an existing small file
//     $testFilePath = __DIR__ . '/test_upload.txt';
//     if (!file_exists($testFilePath)) {
//         file_put_contents($testFilePath, 'This is a test file for Straico API upload.');
//     }
//     if (!file_exists($testFilePath)) {
//         throw new \Exception("Failed to create or find test file at {$testFilePath}");
//     }
//     // Note: The service method constructs the full v0 URL internally
//     $result = $straico->uploadFile($testFilePath);
//     // Optional: Clean up the dummy file
//     // unlink($testFilePath);
//     return $result; // Should contain ['url' => '...']
// });

// // 4. Image Generation (POST /v0/image/generation)
// runTest('Image Generation', function () use ($straico) {
//     // --- !! IMPORTANT !! ---
//     // Ensure the model is available in your account (check listModels output)
//     $params = [
//         'model' => 'openai/dall-e-3', // Replace if needed
//         'description' => 'A futuristic cityscape at sunset, digital art style.',
//         'size' => 'square', // or 'landscape', 'portrait'
//         'variations' => 1 // Keep low for testing to save cost/time
//     ];
//      // Note: The service method constructs the full v0 URL internally
//     return $straico->createImageGeneration($params);
// });

echo "--- All Tests Completed ---\n";

?>