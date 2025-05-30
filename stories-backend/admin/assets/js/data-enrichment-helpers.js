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

        // Filter out options with "unknown" values
        const validOptions = options.filter(option =>
            option.value !== 'Unknown' &&
            option.value !== 'unknown' &&
            option.value !== null &&
            option.value !== undefined &&
            option.value !== ''
        );

        if (validOptions.length === 0) {
            console.log(`📦 No valid options for ${fieldName} - all were unknown/empty`);
            return '';
        }

        validOptions.forEach((option, index) => {
            const confidence = option.confidence || 0;
            const source = option.source || 'unknown';
            const displayValue = formatFieldValue(fieldName, option.value);

            // Clean source display name
            const sourceDisplayName = source === 'google_books' ? 'Google Books' :
                                    source === 'open_library' ? 'OpenLibrary' :
                                    source === 'amazon' ? 'Amazon' :
                                    source === 'amazon_derived' ? 'Amazon' :
                                    source === 'database_recommendation' ? 'Database Match' :
                                    source.replace(/_/g, ' ');

            optionsHtml += `
                <div class="form-check mt-2">
                    <input class="form-check-input" type="radio" name="field_${fieldName}_option" id="field_${fieldName}_${index}" value="${index}">
                    <label class="form-check-label" for="field_${fieldName}_${index}">
                        <strong>${sourceDisplayName} (${confidence}%)</strong>
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

        // Add database recommendation option for publisher field
        if (fieldName === 'publisher') {
            console.log('🏢 PUBLISHER_DEBUG: Processing publisher field options:', options);
            console.log('🏢 PUBLISHER_DEBUG: Field structure:', field);

            // Check if any option has a database recommendation
            const hasRecommendation = options.some(option => option.recommended);
            console.log('🏢 PUBLISHER_DEBUG: Has recommendation:', hasRecommendation);

            // Debug each option
            options.forEach((option, index) => {
                console.log(`🏢 PUBLISHER_DEBUG: Option ${index}:`, {
                    value: option.value,
                    recommended: option.recommended,
                    recommendation_confidence: option.recommendation_confidence,
                    match_type: option.match_type
                });
            });

            if (hasRecommendation) {
                // Show the specific database recommendation
                const recommendedOption = options.find(option => option.recommended);
                console.log('🏢 Recommended option:', recommendedOption);
                optionsHtml += `
                    <div class="form-check mt-3 p-2 bg-light border border-success rounded">
                        <input class="form-check-input" type="radio" name="field_${fieldName}_option" id="field_${fieldName}_database" value="database_match" checked>
                        <label class="form-check-label font-weight-bold text-success" for="field_${fieldName}_database">
                            <span class="badge badge-success">✓ Database Match (Recommended)</span>
                            <div class="mt-1">
                                <i class="fas fa-database"></i> <strong>${recommendedOption.recommended}</strong>
                                <br><small class="text-muted">${recommendedOption.recommendation_confidence}% match - prevents duplicates and maintains data consistency</small>
                            </div>
                        </label>
                    </div>
                `;
            } else {
                console.log('🏢 No specific recommendation found, showing generic database option');
                // Generic database recommendation when no specific match found
                optionsHtml += `
                    <div class="form-check mt-3 p-2 bg-light border border-info rounded">
                        <input class="form-check-input" type="radio" name="field_${fieldName}_option" id="field_${fieldName}_database" value="database_match">
                        <label class="form-check-label font-weight-bold text-info" for="field_${fieldName}_database">
                            <span class="badge badge-info">Database Match</span>
                            <div class="mt-1">
                                <i class="fas fa-database"></i> Use existing publisher from database
                                <br><small class="text-muted">Prevents duplicates and maintains data consistency</small>
                            </div>
                        </label>
                    </div>
                `;
            }
        }

        // Check if any option exactly matches current value
        const hasExactMatch = options.some(option => {
            const currentVal = normalizeValue(field.current_value);
            const newVal = normalizeValue(option.value);
            return currentVal === newVal && currentVal !== '' && currentVal !== null;
        });

        // Apply exact match styling if found
        const exactMatchClass = hasExactMatch ? ' exact-match' : '';
        const disabledClass = bestBenefitLevel === 'not_beneficial' ? ' disabled-field' : '';
        const labelClass = bestBenefitLevel === 'not_beneficial' ? ' text-muted' : '';

        // Calculate combined confidence score from all valid sources
        const totalConfidence = validOptions.reduce((sum, option) => sum + (option.confidence || 0), 0);
        const avgConfidence = Math.round(totalConfidence / validOptions.length);

        // Get unique sources for header display
        const uniqueSources = [...new Set(validOptions.map(option => option.source || 'unknown'))];
        const sourceDisplayNames = uniqueSources.map(source => {
            switch(source) {
                case 'google_books': return 'Google Books';
                case 'open_library': return 'OpenLibrary';
                case 'amazon': return 'Amazon';
                case 'amazon_derived': return 'Amazon';
                case 'database_recommendation': return 'Database';
                default: return source.replace(/_/g, ' ');
            }
        });

        // Create header with sources and combined confidence
        const headerText = sourceDisplayNames.length > 1 ?
            `${label} ${sourceDisplayNames.join(' + ')} (${avgConfidence}%)` :
            `${label} ${sourceDisplayNames[0]} (${avgConfidence}%)`;

        return `
            <div class="col-md-6 mb-3">
                <div class="enrichment-field ${benefitBorder}${exactMatchClass}${disabledClass}" data-field="${fieldName}">
                    <div class="form-check">
                        <input class="form-check-input field-checkbox" type="checkbox"
                               id="field_${fieldName}" name="fields[]" value="${fieldName}" ${bestBenefitLevel === 'not_beneficial' ? 'disabled' : ''}>
                        <label class="form-check-label font-weight-bold${labelClass}" for="field_${fieldName}">
                            ${headerText}
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

    function normalizeValue(value) {
        if (value === null || value === undefined) return '';
        if (Array.isArray(value)) return value.join(',').toLowerCase().trim();
        return String(value).toLowerCase().trim();
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
            // Display purchase links in a user-friendly format
            try {
                const linksData = typeof value === 'string' ? JSON.parse(value) : value;
                if (!linksData || typeof linksData !== 'object') {
                    return '<span class="text-muted">No links available</span>';
                }

                // Format as user-friendly purchase options
                let formattedLinks = '';
                Object.keys(linksData).forEach(format => {
                    const option = linksData[format];
                    if (option && option.price && option.url) {
                        const isSelected = option.is_selected ? ' <span class="badge badge-success">Default</span>' : '';
                        formattedLinks += `
                            <div class="mb-1">
                                <strong>${format}:</strong> ${option.price}${isSelected}
                                <a href="${option.url}" target="_blank" class="btn btn-sm btn-outline-primary ml-2">
                                    <i class="fas fa-external-link-alt"></i> Buy
                                </a>
                            </div>
                        `;
                    }
                });

                return formattedLinks || '<span class="text-muted">No valid purchase options</span>';
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
    window.normalizeValue = normalizeValue;
}
