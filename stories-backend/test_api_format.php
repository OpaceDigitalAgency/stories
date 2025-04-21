<?php
/**
 * API Format Test
 * 
 * This script tests the API response format and displays the results
 * in a user-friendly way.
 */

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);

// HTML header
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>API Format Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            line-height: 1.6;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background: #f5f5f5;
        }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        pre {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .response-toggle {
            cursor: pointer;
            color: blue;
            text-decoration: underline;
        }
        .response-data {
            display: none;
            margin-top: 10px;
        }
    </style>
    <script>
        function toggleResponse(endpoint) {
            var element = document.getElementById('response-' + endpoint);
            var arrow = document.getElementById('arrow-' + endpoint);
            if (element.style.display === 'none') {
                element.style.display = 'block';
                arrow.textContent = '▼';
            } else {
                element.style.display = 'none';
                arrow.textContent = '▶';
            }
        }
    </script>
</head>
<body>
    <h1>API Format Test</h1>

    <h2>API Endpoints</h2>
    <table>
        <tr>
            <th>Endpoint</th>
            <th>Status</th>
            <th>Format</th>
            <th>Details</th>
        </tr>
        <?php
        $endpoints = [
            'stories',
            'authors',
            'games',
            'directory-items',
            'ai-tools'
        ];

        foreach ($endpoints as $endpoint) {
            $url = "https://api.storiesfromtheweb.org/api/v1/$endpoint";
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            echo "<tr>";
            echo "<td>$endpoint</td>";
            echo "<td>" . ($httpCode === 200 ? "<span class='success'>200</span>" : "<span class='error'>$httpCode</span>") . "</td>";

            if ($httpCode === 200) {
                $data = json_decode($response, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    echo "<td class='success'>Valid</td>";
                    echo "<td>";
                    echo "<span class='response-toggle' onclick='toggleResponse(\"$endpoint\")'>";
                    echo "<span id='arrow-$endpoint'>▶</span> View Response</span>";
                    echo "<pre id='response-$endpoint' class='response-data'>";
                    echo htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                    echo "</pre>";
                    echo "</td>";
                } else {
                    echo "<td class='error'>Invalid</td>";
                    echo "<td>Invalid JSON: " . json_last_error_msg();
                    echo "<br><span class='response-toggle' onclick='toggleResponse(\"$endpoint\")'>";
                    echo "<span id='arrow-$endpoint'>▶</span> View Response</span>";
                    echo "<pre id='response-$endpoint' class='response-data'>";
                    echo htmlspecialchars($response);
                    echo "</pre></td>";
                }
            } else {
                echo "<td class='error'>Invalid</td>";
                echo "<td>HTTP Error $httpCode</td>";
            }
            echo "</tr>";
        }
        ?>
    </table>

    <h2>Expected Format</h2>
    <pre>
{
    "status": "success",
    "data": [
        {
            "id": 1,
            "attributes": {
                "title": "Example Story",
                "slug": "example-story",
                "excerpt": "This is an example story...",
                "content": "Full story content here...",
                "publishedAt": "2025-04-21T08:00:00Z",
                "featured": true,
                "averageRating": 4.5,
                "reviewCount": 10,
                "estimatedReadingTime": "5 minutes",
                "isSponsored": false,
                "ageGroup": "12+",
                "needsModeration": false,
                "isSelfPublished": true,
                "isAIEnhanced": false,
                "coverUrl": "https://example.com/cover.jpg",
                "createdAt": "2025-04-21T08:00:00Z",
                "updatedAt": "2025-04-21T08:00:00Z",
                "author": {
                    "id": 1,
                    "name": "John Doe",
                    "slug": "john-doe"
                },
                "tags": [
                    {
                        "id": 1,
                        "name": "Fiction"
                    }
                ]
            }
        }
    ],
    "meta": {
        "pagination": {
            "page": 1,
            "per_page": 25,
            "total": 100,
            "total_pages": 4
        }
    }
}
</pre>

    <p>
        <strong>Note:</strong> All dates should be in ISO 8601 format, all IDs should be integers,
        and all boolean values should be actual booleans (true/false), not strings.
    </p>
</body>
</html>