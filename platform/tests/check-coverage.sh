#!/bin/bash
# Check code coverage and enforce 70% minimum

php artisan test --coverage --min=70

if [ $? -eq 0 ]; then
    echo "✓ Coverage meets 70% baseline"
    exit 0
else
    echo "✗ Coverage below 70% baseline"
    exit 1
fi
