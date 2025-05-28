// Data Enrichment Helper Functions
// Prevents multiple script loading with a guard
if (typeof window.dataEnrichmentHelpersLoaded === 'undefined') {
    window.dataEnrichmentHelpersLoaded = true;

    function createMultiSourceField(fieldName, field, label) {
        let optionsHtml = '';
        const options = field.new_data.options || [];

        // Determine overall benefit level for multi-source field
        let bestBenefitLevel = 'not_beneficial';
        options.forEach((option) => {
            const benefitLevel = determineBenefitLevel(field.current_value, option.value, false);
            if (benefitLevel === 'beneficial') {
                bestBenefitLevel = 'beneficial';
            } else if (benefitLevel === 'questionable' && bestBenefitLevel !== 'beneficial') {
                bestBenefitLevel = 'questionable';
            }
        });

        const benefitClass = getBenefitColorClass(bestBenefitLevel);
        const benefitBorder = getBenefitBorderClass(bestBenefitLevel);

        options.forEach((option, index) => {
            const confidence = option.confidence || 0;
            const source = option.source || 'unknown';
            const displayValue = formatFieldValue(fieldName, option.value);
            const confidenceClass = confidence >= 80 ? 'success' : confidence >= 60 ? 'warning' : confidence >= 30 ? 'info' : 'secondary';
            const sourceClass = source === 'google_books' ? 'success' : 'info';

            optionsHtml += `
                <div class="form-check mt-2">
                    <input class="form-check-input" type="radio" name="field_${fieldName}_option" id="field_${fieldName}_${index}" value="${index}">
                    <label class="form-check-label" for="field_${fieldName}_${index}">
                        <span class="badge badge-${sourceClass}">${source}</span>
                        <span class="badge badge-${confidenceClass} ml-1">(${confidence}%)</span>
                        <div class="mt-1">${displayValue}</div>
                    </label>
                </div>
            `;

            // Add publisher recommendation if available
            if (fieldName === 'publisher' && option.recommended) {
                optionsHtml += `
                    <div class="ml-4 mb-2 p-2 bg-light border-left border-success">
                        <small class="text-success">
                            <i class="fas fa-lightbulb"></i> <strong>Smart Match:</strong>
                            ${option.recommended}
                            <span class="badge badge-success ml-1">${option.recommendation_confidence}%</span>
                            <span class="text-muted">(${option.match_type} match from your database)</span>
                        </small>
                    </div>
                `;
            }
        });

        return `
            <div class="col-md-6 mb-3">
                <div class="enrichment-field ${benefitBorder}" data-field="${fieldName}">
                    <div class="form-check">
                        <input class="form-check-input field-checkbox" type="checkbox"
                               id="field_${fieldName}" name="fields[]" value="${fieldName}" ${bestBenefitLevel === 'not_beneficial' ? 'disabled' : ''}>
                        <label class="form-check-label font-weight-bold" for="field_${fieldName}">
                            ${label}
                            <span class="badge badge-warning ml-2">Multiple Sources</span>
                            ${getBenefitIndicator(bestBenefitLevel)}
                        </label>
                    </div>
                    <div class="mt-2 p-2 ${benefitClass} rounded">
                        <div class="mb-2">
                            <strong>Current Value:</strong> ${formatCurrentValue(fieldName, field.current_value)}
                        </div>
                        <strong>Choose Source:</strong>
                        ${optionsHtml}
                    </div>
                </div>
            </div>
        `;
    }

    function createCurrentOnlyField(fieldName, field, label) {
        return `
            <div class="col-md-6 mb-3">
                <div class="enrichment-field" data-field="${fieldName}">
                    <div class="form-check">
                        <input class="form-check-input field-checkbox" type="checkbox"
                               id="field_${fieldName}" name="fields[]" value="${fieldName}" disabled>
                        <label class="form-check-label font-weight-bold text-muted" for="field_${fieldName}">
                            ${label}
                            <span class="badge badge-secondary ml-2">No New Data</span>
                        </label>
                    </div>
                    <div class="mt-2 p-2 bg-light text-muted rounded">
                        <strong>Current Value:</strong> ${formatCurrentValue(fieldName, field.current_value)}
                    </div>
                </div>
            </div>
        `;
    }

    function formatCurrentValue(fieldName, value) {
        if (!value || value === null || value === 'null' || value === '' || (Array.isArray(value) && value.length === 0)) {
            return '<span class="text-muted">None</span>';
        }

        if (fieldName === 'cover_url') {
            return `<img src="${value}" alt="Current Cover" style="max-height: 40px; max-width: 60px;" class="img-thumbnail">`;
        } else if (fieldName === 'preview_link') {
            return `<a href="${value}" target="_blank" class="btn btn-sm btn-outline-secondary">Current Preview</a>`;
        } else if (fieldName === 'tags') {
            // Handle array values for tags (displayed as genres)
            if (Array.isArray(value)) {
                return value.map(item => `<span class="badge badge-primary mr-1">${item}</span>`).join('');
            } else if (typeof value === 'string' && value.includes(',')) {
                return value.split(',').map(item => `<span class="badge badge-primary mr-1">${item.trim()}</span>`).join('');
            }
            return `<span class="badge badge-primary">${value}</span>`;
        } else if (fieldName === 'publication_date') {
            // Format dates nicely
            const date = new Date(value);
            if (!isNaN(date.getTime())) {
                return date.toLocaleDateString();
            }
            return value;
        } else if (fieldName === 'page_count') {
            return `${value} pages`;
        } else if (fieldName === 'age_range') {
            return `<span class="badge badge-light">${value}</span>`;
        } else if (fieldName === 'maturity_rating') {
            const ratingClass = value === 'NOT_MATURE' ? 'success' : 'warning';
            const displayValue = value === 'NOT_MATURE' ? 'All Ages' : value === 'MATURE' ? '18+' : value;
            return `<span class="badge badge-${ratingClass}">${displayValue}</span>`;
        } else if (fieldName === 'average_rating') {
            return `<span class="text-warning">${'★'.repeat(Math.round(value))}${'☆'.repeat(5-Math.round(value))}</span> ${value}`;
        } else if (fieldName === 'rating_count') {
            return `${value} ratings`;
        } else if (fieldName === 'internet_archive_id') {
            return `<a href="https://archive.org/details/${value}" target="_blank" class="btn btn-sm btn-outline-secondary">Current Archive</a>`;
        } else if (fieldName === 'reading_level') {
            return `<span class="badge badge-secondary">${value}</span>`;
        } else if (fieldName === 'awards') {
            return value.split(',').map(award => `<span class="badge badge-light mr-1">${award.trim()}</span>`).join('');
        } else if (fieldName === 'characters' || fieldName === 'settings') {
            return value.split(',').map(item => `<span class="badge badge-light mr-1">${item.trim()}</span>`).join('');
        } else if (fieldName === 'purchase_links') {
            // Handle JSON purchase links from Amazon
            try {
                const links = JSON.parse(value);
                let html = '<ul class="list-unstyled mb-0">';
                Object.entries(links).forEach(([format, info]) => {
                    html += `<li><strong>${format}:</strong> <a href="${info.url}" target="_blank">${info.price}</a></li>`;
                });
                html += '</ul>';
                return html;
            } catch (e) {
                return value; // Fallback to raw value if not valid JSON
            }
        }

        return value;
    }

    function formatFieldValue(fieldName, value) {
        if (!value || value === null || value === 'null' || value === 'Unknown') {
            return '<span class="text-muted">Unknown</span>';
        }

        if (fieldName === 'cover_url') {
            return `<img src="${value}" alt="Cover" style="max-height: 60px; max-width: 100px;" class="img-thumbnail">`;
        } else if (fieldName === 'preview_link') {
            return `<a href="${value}" target="_blank" class="btn btn-sm btn-outline-primary">View Preview</a>`;
        } else if (fieldName === 'tags') {
            // Handle array values for tags (displayed as genres)
            if (Array.isArray(value)) {
                return value.map(item => `<span class="badge badge-success mr-1">${item}</span>`).join('');
            } else if (typeof value === 'string' && value.includes(',')) {
                return value.split(',').map(item => `<span class="badge badge-success mr-1">${item.trim()}</span>`).join('');
            }
            return `<span class="badge badge-success">${value}</span>`;
        } else if (fieldName === 'publication_date') {
            // Format dates nicely
            const date = new Date(value);
            if (!isNaN(date.getTime())) {
                return date.toLocaleDateString();
            }
            return value;
        } else if (fieldName === 'page_count') {
            return `${value} pages`;
        } else if (fieldName === 'maturity_rating') {
            const ratingClass = value === 'NOT_MATURE' ? 'success' : 'warning';
            const displayValue = value === 'NOT_MATURE' ? 'All Ages' : value === 'MATURE' ? '18+' : value;
            return `<span class="badge badge-${ratingClass}">${displayValue}</span>`;
        } else if (fieldName === 'average_rating') {
            return `<span class="text-warning">${'★'.repeat(Math.round(value))}${'☆'.repeat(5-Math.round(value))}</span> ${value}`;
        } else if (fieldName === 'rating_count') {
            return `${value} ratings`;
        } else if (fieldName === 'internet_archive_id') {
            return `<a href="https://archive.org/details/${value}" target="_blank" class="btn btn-sm btn-outline-info">View on Archive.org</a>`;
        } else if (fieldName === 'reading_level') {
            return `<span class="badge badge-info">${value}</span>`;
        } else if (fieldName === 'awards') {
            return value.split(',').map(award => `<span class="badge badge-warning mr-1">${award.trim()}</span>`).join('');
        } else if (fieldName === 'characters' || fieldName === 'settings') {
            return value.split(',').map(item => `<span class="badge badge-light mr-1">${item.trim()}</span>`).join('');
        } else if (fieldName === 'alternative_isbns') {
            // Display alternative ISBNs in a scrollable container
            const isbns = value.split(',').map(isbn => isbn.trim()).filter(isbn => isbn.length >= 10);
            if (isbns.length === 0) return '<span class="text-muted">None found</span>';

            const isbnBadges = isbns.slice(0, 10).map(isbn => {
                const isbnType = isbn.length === 13 ? 'ISBN-13' : 'ISBN-10';
                return `<span class="badge badge-info mr-1 mb-1" title="${isbnType}: ${isbn}">${isbn}</span>`;
            }).join('');

            const moreCount = isbns.length > 10 ? ` <span class="text-muted">+${isbns.length - 10} more</span>` : '';
            return `<div style="max-height: 100px; overflow-y: auto;">${isbnBadges}${moreCount}</div>`;
        } else if (fieldName === 'purchase_links') {
            // Display purchase links as formatted JSON code
            try {
                const linksData = typeof value === 'string' ? JSON.parse(value) : value;
                if (!linksData || typeof linksData !== 'object') {
                    return '<span class="text-muted">No links available</span>';
                }

                // Format as JSON with proper indentation
                const formattedJson = JSON.stringify(linksData, null, 2);
                return `<pre class="bg-light p-2 rounded" style="font-size: 12px; max-height: 150px; overflow-y: auto;"><code>${formattedJson}</code></pre>`;
            } catch (e) {
                console.error('Error parsing purchase links:', e);
                return '<span class="text-danger">Error parsing links</span>';
            }
        }

        return value;
    }

    // Make functions globally available
    window.createMultiSourceField = createMultiSourceField;
    window.createCurrentOnlyField = createCurrentOnlyField;
    window.formatCurrentValue = formatCurrentValue;
    window.formatFieldValue = formatFieldValue;
}
