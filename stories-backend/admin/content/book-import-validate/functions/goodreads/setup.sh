#!/bin/bash

# Check if pip3 is installed
if ! command -v pip3 &> /dev/null; then
    echo "pip3 is not installed. Installing pip3..."
    if command -v apt-get &> /dev/null; then
        sudo apt-get update
        sudo apt-get install -y python3-pip
    elif command -v yum &> /dev/null; then
        sudo yum install -y python3-pip
    else
        echo "Error: Could not install pip3. Please install it manually."
        exit 1
    fi
fi

# Install required Python packages
echo "Installing required Python packages..."
pip3 install -r requirements.txt

# Make the Python script executable
chmod +x goodreads_book_info.py

echo "Setup complete!"