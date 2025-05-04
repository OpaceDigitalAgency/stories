<?php
/**
 * Form Component
 *
 * A reusable form component for content editing pages.
 *
 * Usage:
 * include '../includes/form-component.php';
 * renderFormStart($action, $method, $options);
 * renderFormField($type, $name, $label, $value, $options);
 * renderFormEnd($submitLabel, $options);
 */

/**
 * Renders the start of a form
 *
 * @param string $action The form action URL
 * @param string $method The form method (GET or POST)
 * @param array $options Additional options for the form
 * @return void
 */
function renderFormStart($action, $method = 'POST', $options = []) {
    // Default options
    $defaults = [
        'id' => 'content-form',
        'class' => 'form',
        'enctype' => $method === 'POST' ? 'multipart/form-data' : '',
        'novalidate' => false,
        'autocomplete' => 'on',
    ];

    // Merge options with defaults
    $options = array_merge($defaults, $options);

    // Render the form start
    ?>
    <form
        id="<?php echo htmlspecialchars($options['id']); ?>"
        class="<?php echo htmlspecialchars($options['class']); ?>"
        action="<?php echo htmlspecialchars($action); ?>"
        method="<?php echo htmlspecialchars($method); ?>"
        <?php if (!empty($options['enctype'])): ?>enctype="<?php echo htmlspecialchars($options['enctype']); ?>"<?php endif; ?>
        <?php if ($options['novalidate']): ?>novalidate<?php endif; ?>
        autocomplete="<?php echo htmlspecialchars($options['autocomplete']); ?>"
    >
    <?php
}

/**
 * Renders a form field
 *
 * @param string $type The field type (text, textarea, select, checkbox, radio, file, hidden)
 * @param string $name The field name
 * @param string $label The field label
 * @param mixed $value The field value
 * @param array $options Additional options for the field
 * @return void
 */
function renderFormField($type, $name, $label, $value = '', $options = []) {
    // Default options
    $defaults = [
        'id' => $name,
        'class' => 'form-control',
        'placeholder' => '',
        'required' => false,
        'disabled' => false,
        'readonly' => false,
        'help_text' => '',
        'error' => '',
        'options' => [], // For select, checkbox, radio
        'multiple' => false, // For select
        'rows' => 5, // For textarea
        'cols' => 40, // For textarea
        'min' => '', // For number, range, date
        'max' => '', // For number, range, date
        'step' => '', // For number, range
        'pattern' => '', // For text, tel, email, password
        'accept' => '', // For file
        'maxlength' => '', // For text, textarea
        'minlength' => '', // For text, textarea
        'autocomplete' => 'on', // For text, email, password
        'wrapper_class' => 'form-group',
        'label_class' => 'form-label',
        'input_wrapper_class' => '',
    ];

    // Merge options with defaults
    $options = array_merge($defaults, $options);

    // Render the field wrapper
    ?>
    <div class="<?php echo htmlspecialchars($options['wrapper_class']); ?>">
        <?php if ($type !== 'hidden'): ?>
            <label for="<?php echo htmlspecialchars($options['id']); ?>" class="<?php echo htmlspecialchars($options['label_class']); ?>">
                <?php echo htmlspecialchars($label); ?>
                <?php if ($options['required']): ?>
                    <span class="required" aria-hidden="true">*</span>
                    <span class="visually-hidden">(required)</span>
                <?php endif; ?>
            </label>
        <?php endif; ?>

        <?php if (!empty($options['input_wrapper_class'])): ?>
            <div class="<?php echo htmlspecialchars($options['input_wrapper_class']); ?>">
        <?php endif; ?>

        <?php
        // Render the field based on type
        switch ($type) {
            case 'textarea':
                ?>
                <textarea
                    id="<?php echo htmlspecialchars($options['id']); ?>"
                    name="<?php echo htmlspecialchars($name); ?>"
                    class="<?php echo htmlspecialchars($options['class']); ?> <?php echo !empty($options['error']) ? 'is-invalid' : ''; ?>"
                    placeholder="<?php echo htmlspecialchars($options['placeholder']); ?>"
                    rows="<?php echo (int)$options['rows']; ?>"
                    cols="<?php echo (int)$options['cols']; ?>"
                    <?php if ($options['required']): ?>required<?php endif; ?>
                    <?php if ($options['disabled']): ?>disabled<?php endif; ?>
                    <?php if ($options['readonly']): ?>readonly<?php endif; ?>
                    <?php if (!empty($options['maxlength'])): ?>maxlength="<?php echo (int)$options['maxlength']; ?>"<?php endif; ?>
                    <?php if (!empty($options['minlength'])): ?>minlength="<?php echo (int)$options['minlength']; ?>"<?php endif; ?>
                    <?php if (!empty($options['pattern'])): ?>pattern="<?php echo htmlspecialchars($options['pattern']); ?>"<?php endif; ?>
                ><?php echo htmlspecialchars($value); ?></textarea>
                <?php
                break;

            case 'select':
                ?>
                <select
                    id="<?php echo htmlspecialchars($options['id']); ?>"
                    name="<?php echo htmlspecialchars($name); ?><?php echo $options['multiple'] ? '[]' : ''; ?>"
                    class="<?php echo htmlspecialchars($options['class']); ?> <?php echo !empty($options['error']) ? 'is-invalid' : ''; ?>"
                    <?php if ($options['required']): ?>required<?php endif; ?>
                    <?php if ($options['disabled']): ?>disabled<?php endif; ?>
                    <?php if ($options['multiple']): ?>multiple<?php endif; ?>
                >
                    <?php foreach ($options['options'] as $optionValue => $optionLabel): ?>
                        <option
                            value="<?php echo htmlspecialchars($optionValue); ?>"
                            <?php
                            if ($options['multiple'] && is_array($value)) {
                                echo in_array($optionValue, $value) ? 'selected' : '';
                            } else {
                                echo $optionValue == $value ? 'selected' : '';
                            }
                            ?>
                        >
                            <?php echo htmlspecialchars($optionLabel); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php
                break;

            case 'checkbox':
                if (empty($options['options'])) {
                    // Single checkbox
                    ?>
                    <div class="form-check">
                        <input
                            type="checkbox"
                            id="<?php echo htmlspecialchars($options['id']); ?>"
                            name="<?php echo htmlspecialchars($name); ?>"
                            class="form-check-input <?php echo !empty($options['error']) ? 'is-invalid' : ''; ?>"
                            value="1"
                            <?php echo $value ? 'checked' : ''; ?>
                            <?php if ($options['required']): ?>required<?php endif; ?>
                            <?php if ($options['disabled']): ?>disabled<?php endif; ?>
                        >
                        <label class="form-check-label" for="<?php echo htmlspecialchars($options['id']); ?>">
                            <?php echo htmlspecialchars($label); ?>
                        </label>
                    </div>
                    <?php
                } else {
                    // Multiple checkboxes
                    foreach ($options['options'] as $optionValue => $optionLabel) {
                        $checkboxId = $options['id'] . '_' . $optionValue;
                        ?>
                        <div class="form-check">
                            <input
                                type="checkbox"
                                id="<?php echo htmlspecialchars($checkboxId); ?>"
                                name="<?php echo htmlspecialchars($name); ?>[]"
                                class="form-check-input <?php echo !empty($options['error']) ? 'is-invalid' : ''; ?>"
                                value="<?php echo htmlspecialchars($optionValue); ?>"
                                <?php echo is_array($value) && in_array($optionValue, $value) ? 'checked' : ''; ?>
                                <?php if ($options['disabled']): ?>disabled<?php endif; ?>
                            >
                            <label class="form-check-label" for="<?php echo htmlspecialchars($checkboxId); ?>">
                                <?php echo htmlspecialchars($optionLabel); ?>
                            </label>
                        </div>
                        <?php
                    }
                }
                break;

            case 'radio':
                foreach ($options['options'] as $optionValue => $optionLabel) {
                    $radioId = $options['id'] . '_' . $optionValue;
                    ?>
                    <div class="form-check">
                        <input
                            type="radio"
                            id="<?php echo htmlspecialchars($radioId); ?>"
                            name="<?php echo htmlspecialchars($name); ?>"
                            class="form-check-input <?php echo !empty($options['error']) ? 'is-invalid' : ''; ?>"
                            value="<?php echo htmlspecialchars($optionValue); ?>"
                            <?php echo $optionValue == $value ? 'checked' : ''; ?>
                            <?php if ($options['required']): ?>required<?php endif; ?>
                            <?php if ($options['disabled']): ?>disabled<?php endif; ?>
                        >
                        <label class="form-check-label" for="<?php echo htmlspecialchars($radioId); ?>">
                            <?php echo htmlspecialchars($optionLabel); ?>
                        </label>
                    </div>
                    <?php
                }
                break;

            case 'file':
                ?>
                <input
                    type="file"
                    id="<?php echo htmlspecialchars($options['id']); ?>"
                    name="<?php echo htmlspecialchars($name); ?>"
                    class="<?php echo htmlspecialchars($options['class']); ?> <?php echo !empty($options['error']) ? 'is-invalid' : ''; ?>"
                    <?php if ($options['required']): ?>required<?php endif; ?>
                    <?php if ($options['disabled']): ?>disabled<?php endif; ?>
                    <?php if (!empty($options['accept'])): ?>accept="<?php echo htmlspecialchars($options['accept']); ?>"<?php endif; ?>
                    <?php if ($options['multiple']): ?>multiple<?php endif; ?>
                >
                <?php
                break;

            case 'hidden':
                ?>
                <input
                    type="hidden"
                    id="<?php echo htmlspecialchars($options['id']); ?>"
                    name="<?php echo htmlspecialchars($name); ?>"
                    value="<?php echo htmlspecialchars($value); ?>"
                >
                <?php
                break;

            default:
                // Default to text input
                ?>
                <input
                    type="<?php echo htmlspecialchars($type); ?>"
                    id="<?php echo htmlspecialchars($options['id']); ?>"
                    name="<?php echo htmlspecialchars($name); ?>"
                    class="<?php echo htmlspecialchars($options['class']); ?> <?php echo !empty($options['error']) ? 'is-invalid' : ''; ?>"
                    value="<?php echo htmlspecialchars($value); ?>"
                    placeholder="<?php echo htmlspecialchars($options['placeholder']); ?>"
                    <?php if ($options['required']): ?>required<?php endif; ?>
                    <?php if ($options['disabled']): ?>disabled<?php endif; ?>
                    <?php if ($options['readonly']): ?>readonly<?php endif; ?>
                    <?php if (!empty($options['min'])): ?>min="<?php echo htmlspecialchars($options['min']); ?>"<?php endif; ?>
                    <?php if (!empty($options['max'])): ?>max="<?php echo htmlspecialchars($options['max']); ?>"<?php endif; ?>
                    <?php if (!empty($options['step'])): ?>step="<?php echo htmlspecialchars($options['step']); ?>"<?php endif; ?>
                    <?php if (!empty($options['pattern'])): ?>pattern="<?php echo htmlspecialchars($options['pattern']); ?>"<?php endif; ?>
                    <?php if (!empty($options['maxlength'])): ?>maxlength="<?php echo (int)$options['maxlength']; ?>"<?php endif; ?>
                    <?php if (!empty($options['minlength'])): ?>minlength="<?php echo (int)$options['minlength']; ?>"<?php endif; ?>
                    autocomplete="<?php echo htmlspecialchars($options['autocomplete']); ?>"
                >
                <?php
                break;
        }
        ?>

        <?php if (!empty($options['error'])): ?>
            <div class="invalid-feedback">
                <?php echo htmlspecialchars($options['error']); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($options['help_text'])): ?>
            <div class="form-text text-muted">
                <?php echo $options['help_text']; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($options['input_wrapper_class'])): ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Renders the end of a form
 *
 * @param string $submitLabel The label for the submit button
 * @param array $options Additional options for the form end
 * @return void
 */
function renderFormEnd($submitLabel = 'Save', $options = []) {
    // Default options
    $defaults = [
        'submit_class' => 'btn btn-primary',
        'cancel_url' => '',
        'cancel_label' => 'Cancel',
        'cancel_class' => 'btn btn-secondary',
        'buttons_wrapper_class' => 'form-buttons',
        'additional_buttons' => '',
    ];

    // Merge options with defaults
    $options = array_merge($defaults, $options);

    // Render the form end
    ?>
    <div class="<?php echo htmlspecialchars($options['buttons_wrapper_class']); ?>">
        <button type="submit" class="<?php echo htmlspecialchars($options['submit_class']); ?>">
            <?php echo htmlspecialchars($submitLabel); ?>
        </button>

        <?php if (!empty($options['cancel_url'])): ?>
            <a href="<?php echo htmlspecialchars($options['cancel_url']); ?>" class="<?php echo htmlspecialchars($options['cancel_class']); ?>">
                <?php echo htmlspecialchars($options['cancel_label']); ?>
            </a>
        <?php endif; ?>

        <?php if (!empty($options['additional_buttons'])): ?>
            <?php echo $options['additional_buttons']; ?>
        <?php endif; ?>
    </div>
    </form>
    <?php
}



