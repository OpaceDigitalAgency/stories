<?php
// Test Amazon scraping for specific ISBN
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once '../../../config/db-connect.php';
    require_once 'book-import-validate/functions/data-enrichment-functions.php';
    echo "<p>✅ Required files loaded successfully</p>";
} catch (Exception $e) {
    echo "<p>❌ Error loading files: " . $e->getMessage() . "</p>";
    exit;
}

// Test ISBN from the user's example - both versions
$testISBN = '9781444004786'; // Full ISBN-13
$testISBN10 = '1444004786'; // ISBN-10

echo "<h2>Testing Amazon Scraping for ISBN: $testISBN</h2>";
echo "<p>Testing both ISBN-13: $testISBN and ISBN-10: $testISBN10</p>";

// Enable debug mode
define('AMAZON_DEBUG', true);

// Test the main scraping function that should extract reading age
echo "<h3>Testing scrapeAmazonBuyingOptions function:</h3>";
$amazonData = scrapeAmazonBuyingOptions($testISBN);

echo "<h4>Raw Amazon Data (ISBN-13):</h4>";
echo "<pre>";
print_r($amazonData);
echo "</pre>";

// Test with ISBN-10 too
$amazonData10 = scrapeAmazonBuyingOptions($testISBN10);
echo "<h4>Raw Amazon Data (ISBN-10):</h4>";
echo "<pre>";
print_r($amazonData10);
echo "</pre>";

// Test age range mapping
if (isset($amazonData['metadata']['reading_age'])) {
    echo "<h3>Age Range Mapping (ISBN-13):</h3>";
    echo "Original Amazon reading age: " . $amazonData['metadata']['reading_age'] . "<br>";

    $mappedAge = mapAmazonAgeRangeToStandard($amazonData['metadata']['reading_age']);
    echo "Mapped to standard range: " . ($mappedAge ?: 'No mapping found') . "<br>";
} else {
    echo "<h3>❌ No reading_age found in Amazon data (ISBN-13)</h3>";
}

if (isset($amazonData10['metadata']['reading_age'])) {
    echo "<h3>Age Range Mapping (ISBN-10):</h3>";
    echo "Original Amazon reading age: " . $amazonData10['metadata']['reading_age'] . "<br>";

    $mappedAge10 = mapAmazonAgeRangeToStandard($amazonData10['metadata']['reading_age']);
    echo "Mapped to standard range: " . ($mappedAge10 ?: 'No mapping found') . "<br>";
} else {
    echo "<h3>❌ No reading_age found in Amazon data (ISBN-10)</h3>";
}

// Test the Amazon enrichment data function
echo "<h3>Amazon Enrichment Data:</h3>";
$amazonEnrichmentData = getAmazonEnrichmentData($testISBN);

echo "<h4>Complete Amazon Enrichment Data:</h4>";
echo "<pre>";
print_r($amazonEnrichmentData);
echo "</pre>";

if (isset($amazonEnrichmentData['fields']['age_range'])) {
    echo "<h4>✅ Age Range Field Data Found:</h4>";
    echo "<pre>";
    print_r($amazonEnrichmentData['fields']['age_range']);
    echo "</pre>";
} else {
    echo "<h4>❌ No age_range field in Amazon enrichment data</h4>";
    echo "<p>Available fields: " . implode(', ', array_keys($amazonEnrichmentData['fields'] ?? [])) . "</p>";
}

// Test Chronicles of Narnia too
echo "<hr><h2>Testing Chronicles of Narnia ISBN: 9780007115617</h2>";
$narniaData = scrapeAmazonBuyingOptions('9780007115617');
echo "<h4>Narnia Amazon Data:</h4>";
echo "<pre>";
print_r($narniaData);
echo "</pre>";

if (isset($narniaData['metadata']['reading_age'])) {
    echo "Narnia reading age: " . $narniaData['metadata']['reading_age'] . "<br>";
    $narniaMapped = mapAmazonAgeRangeToStandard($narniaData['metadata']['reading_age']);
    echo "Mapped: " . ($narniaMapped ?: 'No mapping') . "<br>";
} else {
    echo "❌ No reading_age found in Narnia data<br>";
}

// Test direct URL access
echo "<hr><h3>Direct URL Test:</h3>";
$url = "https://www.amazon.co.uk/dp/$testISBN10";
echo "<p>Testing URL: <a href='$url' target='_blank'>$url</a></p>";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>HTTP Status:</strong> $httpCode</p>";
echo "<p><strong>Response Length:</strong> " . strlen($response) . " characters</p>";

// Look for reading age in the response
if ($response) {
    echo "<h3>Searching for Reading Age in Response:</h3>";

    // Search for reading age patterns
    $patterns = [
        '/<span[^>]*class="a-text-bold"[^>]*>Reading age[^<]*<\/span>[^<]*<span[^>]*>([^<]+)<\/span>/i',
        '/Reading age[^:]*:\s*([^<\n]+)/i',
        '/Reading age[^>]*>([^<]+)</i',
        '/<span>Reading age<\/span>.*?<span[^>]*>([^<]+)<\/span>/is'
    ];

    $found = false;
    foreach ($patterns as $i => $pattern) {
        if (preg_match($pattern, $response, $matches)) {
            echo "<p><strong>Pattern " . ($i + 1) . " found:</strong> " . htmlspecialchars($matches[1]) . "</p>";
            $found = true;
        }
    }

    if (!$found) {
        echo "<p><strong>❌ No reading age patterns found in response</strong></p>";

        // Look for any mention of "age" in the response
        if (preg_match_all('/[^>]*age[^<]*/i', $response, $ageMatches)) {
            echo "<h4>All mentions of 'age' in response:</h4>";
            foreach (array_slice($ageMatches[0], 0, 10) as $i => $match) {
                echo "<p>" . ($i + 1) . ": " . htmlspecialchars(trim($match)) . "</p>";
            }
        }
    }

    // Also check for detail bullets section
    if (preg_match('/<div[^>]*id="detailBullets_feature_div"[^>]*>(.*?)<\/div>/is', $response, $bulletMatch)) {
        echo "<h4>Detail bullets section found - length: " . strlen($bulletMatch[1]) . " characters</h4>";

        // Look for reading age in bullets
        if (preg_match('/reading age/i', $bulletMatch[1])) {
            echo "<p><strong>✅ 'Reading age' found in detail bullets section</strong></p>";
        } else {
            echo "<p><strong>❌ 'Reading age' NOT found in detail bullets section</strong></p>";
        }
    } else {
        echo "<p><strong>❌ Detail bullets section not found</strong></p>";
    }
} else {
    echo "<p><strong>❌ No response received</strong></p>";
}
?>
