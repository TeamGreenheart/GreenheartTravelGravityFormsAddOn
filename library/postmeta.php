<?php

namespace rbt\gftravel;

class Postmeta {

    const META_KEY = 'job_criteria';

    public function __construct() {

        \add_action('add_meta_boxes', [__CLASS__, 'register_metabox']);

        \add_action('save_post', [__CLASS__, 'save_metabox']);

        \add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin_scripts']);

    }

    public static function register_metabox() : void {
        error_log("Registering metabox for job criteria");
        add_meta_box(
            'job_criteria_box',
            'Job Application Criteria',
            [__CLASS__, 'render_metabox'],
            'jobs',
            'normal',
            'default'
        );

    }

    public static function render_metabox($post) : void {

        wp_nonce_field('job_criteria_nonce', 'job_criteria_nonce');
    
        $criteria = get_post_meta(
            $post->ID,
            self::META_KEY,
            true
        );
    
        if (!$criteria) {
    
            $criteria = [
                'relation' => 'AND',
                'rules' => []
            ];
    
        }
    
    ?>
    <div id="job-criteria-builder">
    
        <label>
            Relation
            <select name="job_criteria_relation">
                <option value="AND"
                    <?php selected($criteria['relation'], 'AND'); ?>>
                    AND
                </option>
                <option value="OR"
                    <?php selected($criteria['relation'], 'OR'); ?>>
                    OR
                </option>
            </select>
        </label>
    
        <table class="widefat">
    
            <thead>
                <tr>
                    <th>Field</th>
                    <th>Operator</th>
                    <th>Criteria</th>
                    <th></th>
                </tr>
            </thead>
    
            <tbody id="criteria-rows">
    
                <?php foreach ($criteria['rules'] as $index => $rule) : ?>
    
                    <?php self::render_rule_row($rule, $index); ?>
    
                <?php endforeach; ?>
    
            </tbody>
    
        </table>
    
        <button
            type="button"
            class="button"
            id="add-criteria-row"
        >
            Add Criteria
        </button>
    
    </div>
    <?php
    
    }

    private static function render_rule_row($rule, $index) : void {

        $form_fields = self::get_form_fields();
    
        $field = $rule['field'] ?? '';
        $operator = $rule['operator'] ?? '';
        $criteria = $rule['criteria'] ?? '';
    
        /*
         * Convert array back to textarea string
         */
        if (is_array($criteria)) {
            $criteria = implode("\n", $criteria);
        }
    
        ?>
        <tr>
        
        <td>
    
            <select name="job_criteria_rules[<?php echo $index; ?>][field]">
    
                <option value="">Select field</option>
    
                <?php foreach ($form_fields as $id => $label) : ?>
    
                <option
                    value="<?php echo esc_attr($id); ?>"
                    <?php selected($field, $id); ?>
                >
                    <?php echo esc_html($label); ?>
                </option>
    
                <?php endforeach; ?>
    
            </select>
    
        </td>
        
        <td>
            <select
                name="job_criteria_rules[<?php echo $index; ?>][operator]"
            >
        
                <?php foreach (self::operators() as $op) : ?>
        
                    <option
                        value="<?php echo esc_attr($op); ?>"
                        <?php selected($operator, $op); ?>
                    >
                        <?php echo esc_html($op); ?>
                    </option>
        
                <?php endforeach; ?>
        
            </select>
        </td>
    
        <td>
    
            <textarea
                name="job_criteria_rules[<?php echo $index; ?>][criteria]"
                rows="3"
                style="width:100%;"
            ><?php echo esc_textarea($criteria); ?></textarea>
    
        </td>
        
        <td>
            <button type="button" class="remove-row button">
                Remove
            </button>
        </td>
        
        </tr>
        <?php
    
    }

    private static function operators() : array {

        return [
        
        '>',
        '<',
        '>=',
        '<=',
        '=',
        '!=',
        'contains',
        'between',
        'in'
        
        ];
        
    }

    public static function save_metabox($post_id) : void {

        if (!isset($_POST['job_criteria_nonce'])) {
            return;
        }
    
        if (!wp_verify_nonce(
            $_POST['job_criteria_nonce'],
            'job_criteria_nonce'
        )) {
            return;
        }
    
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
    
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    
        $relation = sanitize_text_field(
            $_POST['job_criteria_relation'] ?? 'AND'
        );
    
        $rules = $_POST['job_criteria_rules'] ?? [];
    
        $clean_rules = [];
    
        foreach ($rules as $rule) {
    
            if (empty($rule['field'])) {
                continue;
            }
    
            $operator = sanitize_text_field(
                $rule['operator'] ?? ''
            );
    
            $criteria = trim(
                $rule['criteria'] ?? ''
            );
    
            /*
             * Convert textarea input to array
             * when operator = IN
             */
            if ($operator === 'in') {
    
                $criteria = array_filter(
                    array_map(
                        'trim',
                        preg_split('/[\r\n,]+/', $criteria)
                    )
                );
    
            }
    
            $clean_rules[] = [
    
                'field' => (int) $rule['field'],
    
                'operator' => $operator,
    
                'criteria' => $criteria
    
            ];
    
        }
    
        $payload = [
    
            'relation' => $relation,
    
            'rules' => $clean_rules
    
        ];
    
        update_post_meta(
    
            $post_id,
    
            self::META_KEY,
    
            $payload
    
        );
    
    }

    public static function getCriteriaForJob(int $post_id) : array {

        $criteria = get_post_meta(
            $post_id,
            self::META_KEY,
            true
        );

        if (!$criteria || !is_array($criteria)) {
            return [];
        }

        return $criteria;

    }

    public static function enqueue_admin_scripts($hook) : void {

        global $post;
    
        if ($hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }
    
        if (!$post || $post->post_type !== 'jobs') {
            return;
        }
    
        \wp_enqueue_script(
            'job-criteria-builder',
            \GreenheartTravelGF::get_plugin_url() . '/library/admin/criteria.js',
            ['jquery'],
            false,
            true
        );

        \wp_localize_script(
            'job-criteria-builder',
            'criteriaBuilderData',
            [
                'fields' => self::get_form_fields()
            ]
        );
    
    }

    private static function get_form_fields() : array {

        if (!class_exists('GFAPI')) {
            return [];
        }
    
        $form_id = \GreenheartTravelGF::$formId ?? 0;
    
        if (!$form_id) {
            return [];
        }
    
        $form = \GFAPI::get_form($form_id);
    
        if (!$form || empty($form['fields'])) {
            return [];
        }
    
        $fields = [];
    
        foreach ($form['fields'] as $field) {
    
            if (empty($field->id)) {
                continue;
            }
    
            /*
             * Optional: skip non-input fields
             */
            if (in_array($field->type, [
                'section',
                'html',
                'page'
            ])) {
                continue;
            }
    
            $fields[$field->id] = $field->label
                ?: 'Field ' . $field->id;
    
        }
    
        return $fields;
    
    }

}