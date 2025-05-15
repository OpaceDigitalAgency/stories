<?php
/**
 * Review Analyzer
 *
 * This class analyzes book reviews using OpenAI's API to extract age-related content,
 * identify content flags, and generate summaries.
 */

namespace Services\AI;

class ReviewAnalyzer {
    /**
     * @var string OpenAI API key
     */
    private $apiKey;

    /**
     * @var string OpenAI API model to use
     */
    private $model;

    /**
     * @var string|null Last error message
     */
    private $lastError = null;

    /**
     * @var \PDO Database connection
     */
    private $db;

    /**
     * Constructor
     *
     * @param \PDO|string $dbOrApiKey Database connection or OpenAI API key
     * @param string $model OpenAI model to use (default: gpt-4o)
     */
    public function __construct($dbOrApiKey, string $model = 'gpt-4o') {
        if ($dbOrApiKey instanceof \PDO) {
            $this->db = $dbOrApiKey;

            // Get API key from settings
            try {
                $stmt = $this->db->prepare("
                    SELECT setting_value
                    FROM settings
                    WHERE setting_name = 'openai_api_key'
                ");
                $stmt->execute();
                $this->apiKey = $stmt->fetchColumn();

                // Get model from settings if available
                $modelStmt = $this->db->prepare("
                    SELECT setting_value
                    FROM settings
                    WHERE setting_name = 'ai_default_model'
                ");
                $modelStmt->execute();
                $defaultModel = $modelStmt->fetchColumn();

                if ($defaultModel) {
                    $this->model = $defaultModel;
                } else {
                    $this->model = $model;
                }
            } catch (\Exception $e) {
                $this->lastError = "Error getting API key from settings: " . $e->getMessage();
                $this->apiKey = '';
                $this->model = $model;
            }
        } else {
            $this->apiKey = $dbOrApiKey;
            $this->model = $model;
        }
    }

    /**
     * Get the last error message
     *
     * @return string|null The last error message or null if no error occurred
     */
    public function getLastError(): ?string {
        return $this->lastError;
    }

    /**
     * Set the OpenAI model to use
     *
     * @param string $model The model name
     */
    public function setModel(string $model): void {
        $this->model = $model;
    }

    /**
     * Analyze a review for age suitability
     *
     * @param string $reviewText The review text to analyze
     * @param string $bookTitle The book title (optional)
     * @param string $bookAuthor The book author (optional)
     * @return array|null Analysis results or null on error
     */
    public function analyzeReviewForAgeSuitability(string $reviewText, string $bookTitle = '', string $bookAuthor = ''): ?array {
        // Prepare the prompt
        $prompt = $this->prepareAgeSuitabilityPrompt($reviewText, $bookTitle, $bookAuthor);

        // Call the OpenAI API
        $response = $this->callOpenAI($prompt);

        if ($response === null) {
            return null;
        }

        // Parse the response
        return $this->parseAgeSuitabilityResponse($response);
    }

    /**
     * Prepare the prompt for age suitability analysis
     *
     * @param string $reviewText The review text to analyze
     * @param string $bookTitle The book title
     * @param string $bookAuthor The book author
     * @return string The prepared prompt
     */
    private function prepareAgeSuitabilityPrompt(string $reviewText, string $bookTitle, string $bookAuthor): string {
        $bookInfo = '';
        if (!empty($bookTitle)) {
            $bookInfo .= "Book Title: $bookTitle\n";
        }
        if (!empty($bookAuthor)) {
            $bookInfo .= "Book Author: $bookAuthor\n";
        }

        return <<<EOT
You are an expert in children's literature and child development. Analyze the following book review to extract information about age suitability and content appropriateness.

$bookInfo
Review: $reviewText

Please analyze this review and provide the following information in JSON format:
1. suitability_score: A decimal number between 0 and 1 representing how suitable this book is for children based on the review (0 = not suitable, 1 = highly suitable)
2. content_flags: An array of content flags mentioned in the review (e.g., "violence", "scary_content", "mature_themes", "language", "death", etc.)
3. age_mentions: An array of specific age ranges or grades mentioned in the review
4. ai_summary: A concise summary (1-2 sentences) focusing on age-appropriateness and content suitability

Return your analysis in the following JSON format:
{
  "suitability_score": 0.0 to 1.0,
  "content_flags": ["flag1", "flag2", ...],
  "age_mentions": ["age range 1", "age range 2", ...],
  "ai_summary": "Summary text focusing on age-appropriateness."
}
EOT;
    }

    /**
     * Parse the OpenAI response for age suitability analysis
     *
     * @param string $response The OpenAI response
     * @return array|null The parsed response or null on error
     */
    private function parseAgeSuitabilityResponse(string $response): ?array {
        try {
            // Extract JSON from the response
            if (preg_match('/```json\s*(.*?)\s*```/s', $response, $matches)) {
                $jsonStr = $matches[1];
            } else if (preg_match('/\{.*\}/s', $response, $matches)) {
                $jsonStr = $matches[0];
            } else {
                $jsonStr = $response;
            }

            $data = json_decode($jsonStr, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->lastError = "Failed to parse JSON response: " . json_last_error_msg();
                return null;
            }

            // Ensure required fields are present
            $requiredFields = ['suitability_score', 'content_flags', 'ai_summary'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    $this->lastError = "Missing required field in response: $field";
                    return null;
                }
            }

            // Ensure suitability_score is between 0 and 1
            $data['suitability_score'] = max(0, min(1, (float)$data['suitability_score']));

            // Ensure content_flags is an array
            if (!is_array($data['content_flags'])) {
                $data['content_flags'] = [];
            }

            // Ensure age_mentions is an array
            if (!isset($data['age_mentions']) || !is_array($data['age_mentions'])) {
                $data['age_mentions'] = [];
            }

            return $data;
        } catch (\Exception $e) {
            $this->lastError = "Error parsing response: " . $e->getMessage();
            return null;
        }
    }

    /**
     * Call the OpenAI API
     *
     * @param string $prompt The prompt to send
     * @return string|null The response or null on error
     */
    private function callOpenAI(string $prompt): ?string {
        $url = 'https://api.openai.com/v1/chat/completions';

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ];

        $data = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an expert in children\'s literature and child development.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.3,
            'max_tokens' => 500
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);

        if ($response === false) {
            $this->lastError = "cURL Error: " . curl_error($ch);
            curl_close($ch);
            return null;
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $responseData = json_decode($response, true);
            $errorMessage = isset($responseData['error']['message'])
                ? $responseData['error']['message']
                : "HTTP Error: $httpCode";

            $this->lastError = "OpenAI API Error: $errorMessage";
            return null;
        }

        $responseData = json_decode($response, true);

        if (!isset($responseData['choices'][0]['message']['content'])) {
            $this->lastError = "Invalid response format from OpenAI API";
            return null;
        }

        return $responseData['choices'][0]['message']['content'];
    }

    /**
     * Analyze a review in the database
     *
     * @param int $reviewId The review ID
     * @return bool True if successful, false otherwise
     */
    public function analyzeReview(int $reviewId): bool {
        if (!$this->db) {
            $this->lastError = "Database connection not available";
            return false;
        }

        try {
            // Get the review
            $stmt = $this->db->prepare("
                SELECT r.*, d.title as book_title, a.name as author_name
                FROM reviews r
                LEFT JOIN directory_items d ON r.book_id = d.id
                LEFT JOIN book_authors ba ON d.id = ba.directory_item_id AND ba.role = 'author'
                LEFT JOIN authors a ON ba.author_id = a.id
                WHERE r.id = ?
            ");
            $stmt->execute([$reviewId]);
            $review = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$review) {
                $this->lastError = "Review not found";
                return false;
            }

            // Skip if no API key
            if (empty($this->apiKey)) {
                $this->lastError = "OpenAI API key not available";
                return false;
            }

            // Analyze the review
            $analysis = $this->analyzeReviewForAgeSuitability(
                $review['review_text'],
                $review['book_title'] ?? '',
                $review['author_name'] ?? ''
            );

            if ($analysis === null) {
                return false;
            }

            // Update the review with the analysis results
            $updateStmt = $this->db->prepare("
                UPDATE reviews
                SET
                    ai_summary = ?,
                    suitability_score = ?,
                    content_flags = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");

            $updateStmt->execute([
                $analysis['ai_summary'],
                $analysis['suitability_score'],
                json_encode($analysis['content_flags']),
                $reviewId
            ]);

            return true;
        } catch (\Exception $e) {
            $this->lastError = "Error analyzing review: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Batch analyze multiple reviews
     *
     * @param array $reviews Array of review texts to analyze
     * @param string $bookTitle The book title (optional)
     * @param string $bookAuthor The book author (optional)
     * @return array Array of analysis results (null for failed analyses)
     */
    public function batchAnalyzeReviews(array $reviews, string $bookTitle = '', string $bookAuthor = ''): array {
        $results = [];

        foreach ($reviews as $index => $reviewText) {
            // Add a small delay between requests to avoid rate limiting
            if ($index > 0) {
                usleep(500000); // 500ms delay
            }

            $results[] = $this->analyzeReviewForAgeSuitability($reviewText, $bookTitle, $bookAuthor);
        }

        return $results;
    }
}
