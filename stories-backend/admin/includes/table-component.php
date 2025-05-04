<?php
/**
 * Table Component
 *
 * A reusable table component for content listing pages.
 *
 * Usage:
 * include '../includes/table-component.php';
 * renderEnhancedTable($items, $columns, $options);
 */

/**
 * Renders an enhanced table for the specified content
 *
 * @param array $items The items to display in the table
 * @param array $columns The columns to display (format: ['key' => 'Label'])
 * @param array $options Additional options for the table
 * @return void
 */
function renderEnhancedTable($items, $columns, $options = []) {
    // Default options
    $defaults = [
        'id' => 'data-table',
        'class' => 'table',
        'empty_message' => 'No items found.',
        'checkbox_column' => true,
        'actions_column' => true,
        'actions' => [
            'view' => true,
            'edit' => true,
            'delete' => true
        ],
        'content_type' => 'item', // Used for aria labels
        'id_field' => 'id',
        'name_field' => 'name', // Used for aria labels
        'view_url' => 'view-{content_type}.php?id={id}',
        'edit_url' => '{content_type}-form.php?id={id}',
        'delete_url' => 'delete-{content_type}.php',
        'custom_formatters' => [], // Custom formatters for specific columns
        'row_classes' => [], // Custom classes for specific rows
    ];

    // Merge options with defaults
    $options = array_merge($defaults, $options);

    // Replace placeholders in URLs
    // Properly handle singular forms
    $singularForms = [
        'stories' => 'story',
        'categories' => 'category',
        'properties' => 'property',
        'directories' => 'directory',
        // Add more special cases as needed
    ];

    if (isset($singularForms[$options['content_type']])) {
        $content_type_singular = $singularForms[$options['content_type']];
    } else {
        // Default behavior for regular plurals
        $content_type_singular = rtrim($options['content_type'], 's');
    }

    $options['view_url'] = str_replace('{content_type}', $content_type_singular, $options['view_url']);
    $options['edit_url'] = str_replace('{content_type}', $content_type_singular, $options['edit_url']);
    $options['delete_url'] = str_replace('{content_type}', $content_type_singular, $options['delete_url']);

    // Render the table
    ?>
    <div class="table-container">
        <table id="<?php echo htmlspecialchars($options['id']); ?>" class="<?php echo htmlspecialchars($options['class']); ?>">
            <thead>
                <tr>
                    <?php if ($options['checkbox_column']): ?>
                        <th class="checkbox-column">
                            <input type="checkbox" id="select-all" aria-label="Select all <?php echo htmlspecialchars($options['content_type']); ?>">
                        </th>
                    <?php endif; ?>

                    <?php foreach ($columns as $key => $label): ?>
                        <th><?php echo htmlspecialchars($label); ?></th>
                    <?php endforeach; ?>

                    <?php if ($options['actions_column']): ?>
                        <th>Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="<?php echo count($columns) + ($options['checkbox_column'] ? 1 : 0) + ($options['actions_column'] ? 1 : 0); ?>" class="text-center">
                            <?php echo htmlspecialchars($options['empty_message']); ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <?php
                        // Determine row classes
                        $rowClass = '';
                        foreach ($options['row_classes'] as $class => $condition) {
                            if (is_callable($condition) && $condition($item)) {
                                $rowClass .= ' ' . $class;
                            }
                        }
                        ?>
                        <tr class="<?php echo trim($rowClass); ?>">
                            <?php if ($options['checkbox_column']): ?>
                                <td class="checkbox-column">
                                    <input
                                        type="checkbox"
                                        class="bulk-checkbox"
                                        name="selected_ids[]"
                                        value="<?php echo $item[$options['id_field']]; ?>"
                                        aria-label="Select <?php echo htmlspecialchars($content_type_singular); ?>: <?php echo htmlspecialchars($item[$options['name_field']] ?? ''); ?>"
                                    >
                                </td>
                            <?php endif; ?>

                            <?php foreach ($columns as $key => $label): ?>
                                <td>
                                    <?php
                                    // Check if there's a custom formatter for this column
                                    if (isset($options['custom_formatters'][$key]) && is_callable($options['custom_formatters'][$key])) {
                                        echo $options['custom_formatters'][$key]($item, $key);
                                    } else {
                                        // Default formatting
                                        echo isset($item[$key]) ? htmlspecialchars($item[$key]) : '';
                                    }
                                    ?>
                                </td>
                            <?php endforeach; ?>

                            <?php if ($options['actions_column']): ?>
                                <td>
                                    <div class="table-actions">
                                        <?php if ($options['actions']['view']): ?>
                                            <a
                                                href="<?php echo str_replace('{id}', $item[$options['id_field']], $options['view_url']); ?>"
                                                class="btn btn-info btn-sm"
                                                aria-label="View <?php echo htmlspecialchars($content_type_singular); ?>: <?php echo htmlspecialchars($item[$options['name_field']] ?? ''); ?>"
                                            >
                                                <i class="fas fa-eye" aria-hidden="true"></i> View
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($options['actions']['edit']): ?>
                                            <a
                                                href="<?php echo str_replace('{id}', $item[$options['id_field']], $options['edit_url']); ?>"
                                                class="btn btn-primary btn-sm"
                                                aria-label="Edit <?php echo htmlspecialchars($content_type_singular); ?>: <?php echo htmlspecialchars($item[$options['name_field']] ?? ''); ?>"
                                            >
                                                <i class="fas fa-edit" aria-hidden="true"></i> Edit
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($options['actions']['delete']): ?>
                                            <form method="POST" action="<?php echo $options['delete_url']; ?>" style="display: inline;">
                                                <input type="hidden" name="id" value="<?php echo $item[$options['id_field']]; ?>">
                                                <button
                                                    type="submit"
                                                    class="btn btn-danger btn-sm"
                                                    onclick="return confirm('Are you sure you want to delete this <?php echo htmlspecialchars($content_type_singular); ?>?')"
                                                    aria-label="Delete <?php echo htmlspecialchars($content_type_singular); ?>: <?php echo htmlspecialchars($item[$options['name_field']] ?? ''); ?>"
                                                >
                                                    <i class="fas fa-trash-alt" aria-hidden="true"></i> Delete
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if (isset($options['custom_actions']) && is_callable($options['custom_actions'])): ?>
                                            <?php echo $options['custom_actions']($item); ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}


