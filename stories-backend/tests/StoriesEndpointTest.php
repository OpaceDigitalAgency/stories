<?php
namespace Tests;

use PHPUnit\Framework\TestCase;

class StoriesEndpointTest extends TestCase
{
    private $baseUrl = 'http://localhost/api/v1';

    public function testStoriesEndpointReturnsCorrectFormat()
    {
        // Make request to stories endpoint
        $response = file_get_contents($this->baseUrl . '/stories?populate=*');
        $this->assertNotFalse($response, 'Failed to get response from stories endpoint');

        // Decode JSON response
        $stories = json_decode($response, true);
        $this->assertNotNull($stories, 'Failed to decode JSON response');
        $this->assertIsArray($stories, 'Response should be an array');

        // If we have stories, verify the first one
        if (count($stories) > 0) {
            $story = $stories[0];

            // Check required fields exist
            $this->assertArrayHasKey('id', $story);
            $this->assertArrayHasKey('title', $story);
            $this->assertArrayHasKey('slug', $story);
            $this->assertArrayHasKey('excerpt', $story);
            $this->assertArrayHasKey('content', $story);
            $this->assertArrayHasKey('publishedAt', $story);
            $this->assertArrayHasKey('featured', $story);
            $this->assertArrayHasKey('rating', $story);
            $this->assertArrayHasKey('reviewCount', $story);
            $this->assertArrayHasKey('estimatedReadingTime', $story);
            $this->assertArrayHasKey('isSponsored', $story);
            $this->assertArrayHasKey('ageGroup', $story);
            $this->assertArrayHasKey('needsModeration', $story);
            $this->assertArrayHasKey('isSelfPublished', $story);
            $this->assertArrayHasKey('isAIEnhanced', $story);
            $this->assertArrayHasKey('coverImage', $story);
            $this->assertArrayHasKey('tags', $story);
            $this->assertArrayHasKey('author', $story);

            // Verify field types
            $this->assertIsInt($story['id']);
            $this->assertIsString($story['title']);
            $this->assertIsString($story['slug']);
            $this->assertIsString($story['excerpt']);
            $this->assertIsString($story['content']);
            $this->assertIsString($story['publishedAt']);
            $this->assertIsBool($story['featured']);
            $this->assertIsNumeric($story['rating']);
            $this->assertIsInt($story['reviewCount']);
            $this->assertIsString($story['estimatedReadingTime']);
            $this->assertIsBool($story['isSponsored']);
            $this->assertIsString($story['ageGroup']);
            $this->assertIsBool($story['needsModeration']);
            $this->assertIsBool($story['isSelfPublished']);
            $this->assertIsBool($story['isAIEnhanced']);
            $this->assertIsString($story['coverImage']);
            $this->assertIsArray($story['tags']);

            // Verify author structure
            $this->assertIsArray($story['author']);
            $this->assertArrayHasKey('name', $story['author']);
            $this->assertArrayHasKey('slug', $story['author']);
            $this->assertArrayHasKey('avatar', $story['author']);
            $this->assertIsString($story['author']['name']);
            $this->assertIsString($story['author']['slug']);
            $this->assertIsString($story['author']['avatar']);

            // Verify publishedAt is in ISO-8601 format
            $date = \DateTime::createFromFormat(\DateTime::ISO8601, $story['publishedAt']);
            $this->assertInstanceOf(\DateTime::class, $date, 'publishedAt should be in ISO-8601 format');
        }
    }
}