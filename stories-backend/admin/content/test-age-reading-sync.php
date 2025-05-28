<?php
/**
 * Test script to verify age range and reading level synchronization
 * This script tests the complete standardized system
 */

// Include database connection
require_once '../db-connect.php';

// Standard age ranges based on UK education system - COMPLETE LIST
$ageRangeList = [
    '0-12 months',
    '12-24 months', 
    '2-3 years',
    '3-4 years',
    '4-5 years',
    '5-6 years',
    '6-7 years',
    '7-8 years',
    '8-9 years',
    '9-10 years',
    '10-11 years',
    '11-14 years',
    '14-16 years',
    '16-18 years',
    '18+ years',
    'Unknown'
];

// Standard reading levels based on UK education system - COMPLETE LIST
$readingLevelList = [
    'Pre-literacy (Sensory)',
    'Pre-literacy (Naming)',
    'Pre-literacy (Mimicry)',
    'Early Pre-reader',
    'Beginning Reader',
    'Early Reader',
    'Developing Reader',
    'Transitional Reader',
    'Fluent Reader',
    'Advanced Reader',
    'Proficient Reader'
];

// Age to reading mapping
$ageToReadingMapping = [
    '0-12 months' => 'Pre-literacy (Sensory)',
    '12-24 months' => 'Pre-literacy (Naming)',
    '2-3 years' => 'Pre-literacy (Mimicry)',
    '3-4 years' => 'Early Pre-reader',
    '4-5 years' => 'Beginning Reader',
    '5-6 years' => 'Early Reader',
    '6-7 years' => 'Developing Reader',
    '7-8 years' => 'Transitional Reader',
    '8-9 years' => 'Fluent Reader',
    '9-10 years' => 'Fluent Reader',
    '10-11 years' => 'Fluent Reader',
    '11-14 years' => 'Advanced Reader',
    '14-16 years' => 'Advanced Reader',
    '16-18 years' => 'Advanced Reader',
    '18+ years' => 'Proficient Reader',
    'Unknown' => '' // No automatic sync for unknown
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Age Range & Reading Level Sync Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .test-container { max-width: 800px; margin: 2rem auto; padding: 2rem; }
        .mapping-table { font-size: 0.9rem; }
        .sync-test { background: #f8f9fa; padding: 1rem; border-radius: 0.5rem; margin: 1rem 0; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>Age Range & Reading Level Synchronization Test</h1>
        
        <div class="row">
            <div class="col-md-6">
                <h3>Age Range Options (<?php echo count($ageRangeList); ?> total)</h3>
                <div class="sync-test">
                    <label for="test_age_range">Age Range:</label>
                    <select id="test_age_range" class="form-control" onchange="syncReadingFromAge()">
                        <option value="">Select Age Range</option>
                        <?php foreach ($ageRangeList as $ageRange): ?>
                            <option value="<?php echo htmlspecialchars($ageRange); ?>">
                                <?php echo htmlspecialchars($ageRange); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="col-md-6">
                <h3>Reading Level Options (<?php echo count($readingLevelList); ?> total)</h3>
                <div class="sync-test">
                    <label for="test_reading_level">Reading Level:</label>
                    <select id="test_reading_level" class="form-control" onchange="syncAgeFromReading()">
                        <option value="">Select Reading Level</option>
                        <?php foreach ($readingLevelList as $readingLevel): ?>
                            <option value="<?php echo htmlspecialchars($readingLevel); ?>">
                                <?php echo htmlspecialchars($readingLevel); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="sync-test">
            <h4>Synchronization Test Results</h4>
            <div id="sync_results">Select an age range or reading level to test synchronization...</div>
        </div>
        
        <h3>Complete Mapping Table</h3>
        <table class="table table-striped mapping-table">
            <thead>
                <tr>
                    <th>Age Group</th>
                    <th>School Year</th>
                    <th>Reading Stage</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $schoolYears = [
                    '0-12 months' => '-',
                    '12-24 months' => '-',
                    '2-3 years' => '-',
                    '3-4 years' => '-',
                    '4-5 years' => 'Reception',
                    '5-6 years' => 'Year 1',
                    '6-7 years' => 'Year 2',
                    '7-8 years' => 'Year 3',
                    '8-9 years' => 'Year 4',
                    '9-10 years' => 'Year 5',
                    '10-11 years' => 'Year 6',
                    '11-14 years' => 'Years 7-9',
                    '14-16 years' => 'Years 10-11',
                    '16-18 years' => 'Years 12-13',
                    '18+ years' => 'Adult',
                    'Unknown' => 'N/A'
                ];
                
                foreach ($ageRangeList as $ageRange):
                    $readingLevel = $ageToReadingMapping[$ageRange] ?? 'Not mapped';
                    $schoolYear = $schoolYears[$ageRange] ?? 'Unknown';
                    $status = in_array($readingLevel, $readingLevelList) ? 'success' : 'error';
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($ageRange); ?></td>
                    <td><?php echo htmlspecialchars($schoolYear); ?></td>
                    <td class="<?php echo $status; ?>">
                        <?php echo htmlspecialchars($readingLevel); ?>
                    </td>
                    <td>
                        <?php if ($status === 'success'): ?>
                            <span class="success">✓ Mapped</span>
                        <?php else: ?>
                            <span class="error">✗ Missing</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="alert alert-info">
            <h5>Test Summary:</h5>
            <ul>
                <li><strong>Age Ranges:</strong> <?php echo count($ageRangeList); ?> standard options</li>
                <li><strong>Reading Levels:</strong> <?php echo count($readingLevelList); ?> standard options</li>
                <li><strong>Mappings:</strong> <?php echo count(array_filter($ageToReadingMapping)); ?> age-to-reading mappings</li>
                <li><strong>Coverage:</strong> All age ranges from 0-12 months to 18+ years included</li>
            </ul>
        </div>
    </div>

    <script>
        // Age to reading mapping (JavaScript version)
        const ageToReadingMapping = <?php echo json_encode($ageToReadingMapping); ?>;
        
        // Reading to age mapping (reverse)
        const readingToAgeMapping = {};
        Object.keys(ageToReadingMapping).forEach(age => {
            const reading = ageToReadingMapping[age];
            if (reading && reading !== '') {
                readingToAgeMapping[reading] = age;
            }
        });
        
        function syncReadingFromAge() {
            const ageSelect = document.getElementById('test_age_range');
            const readingSelect = document.getElementById('test_reading_level');
            const resultsDiv = document.getElementById('sync_results');
            
            const selectedAge = ageSelect.value;
            const targetReading = ageToReadingMapping[selectedAge];
            
            if (targetReading) {
                // Find and select the reading level
                for (let option of readingSelect.options) {
                    if (option.value === targetReading) {
                        option.selected = true;
                        resultsDiv.innerHTML = `<span class="success">✓ Synchronized:</span> ${selectedAge} → ${targetReading}`;
                        return;
                    }
                }
                resultsDiv.innerHTML = `<span class="error">✗ Failed:</span> Could not find reading level "${targetReading}"`;
            } else {
                readingSelect.selectedIndex = 0;
                resultsDiv.innerHTML = `<span class="error">✗ No mapping:</span> No reading level mapped for "${selectedAge}"`;
            }
        }
        
        function syncAgeFromReading() {
            const ageSelect = document.getElementById('test_age_range');
            const readingSelect = document.getElementById('test_reading_level');
            const resultsDiv = document.getElementById('sync_results');
            
            const selectedReading = readingSelect.value;
            const targetAge = readingToAgeMapping[selectedReading];
            
            if (targetAge) {
                // Find and select the age range
                for (let option of ageSelect.options) {
                    if (option.value === targetAge) {
                        option.selected = true;
                        resultsDiv.innerHTML = `<span class="success">✓ Synchronized:</span> ${selectedReading} → ${targetAge}`;
                        return;
                    }
                }
                resultsDiv.innerHTML = `<span class="error">✗ Failed:</span> Could not find age range "${targetAge}"`;
            } else {
                ageSelect.selectedIndex = 0;
                resultsDiv.innerHTML = `<span class="error">✗ No mapping:</span> No age range mapped for "${selectedReading}"`;
            }
        }
    </script>
</body>
</html>
