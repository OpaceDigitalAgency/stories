#!/bin/bash
# Run review scraper tests
# Usage: ./run-tests.sh <isbn> <limit>
# Example: ./run-tests.sh 9780007416851 50

# Check if ISBN is provided
if [ -z "$1" ]; then
  echo "Usage: ./run-tests.sh <isbn> <limit>"
  echo "Example: ./run-tests.sh 9780007416851 50"
  exit 1
fi

# Set variables
ISBN=$1
LIMIT=${2:-50}  # Default to 50 if not provided
BASE_DIR=$(dirname "$0")
cd "$BASE_DIR/.."

echo "====================================================="
echo "Testing Amazon Review Scraper"
echo "====================================================="
php tests/test-amazon-scraper.php $ISBN $LIMIT

echo ""
echo "====================================================="
echo "Testing Goodreads Review Scraper"
echo "====================================================="
php tests/test-goodreads-scraper.php $ISBN $LIMIT

echo ""
echo "====================================================="
echo "Testing All Review Scrapers"
echo "====================================================="
php tests/test-all-scrapers.php $ISBN $LIMIT

echo ""
echo "====================================================="
echo "Tests completed!"
echo "====================================================="
