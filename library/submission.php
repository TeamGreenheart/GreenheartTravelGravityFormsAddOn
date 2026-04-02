<?php

namespace rbt\gftravel;

class Submission {


    /*
     * Constructor
     */
    public function __construct() {

        \add_action(
            'gform_pre_submission_' . \GreenheartTravelGF::$formId,
            [$this, 'evaluateSubmission'],
            10,
            2
        );

    }

    /*
     * Get job post ID from submission
     */
    public function getJobPostId() : int {

        return (int) \rgpost('input_7');

    }

    /*
     * Store pass/fail result into hidden GF field
     */
    private function setPassedCriteria(bool $result) : void {

        $_POST['input_13'] = $result ? 1 : 0;

    }

    /*
     * Entry evaluation entrypoint
     */
    public function evaluateSubmission($form) : void {

        add_filter('greenheart_gravity_forms_thankyou_redirect', function($url, $entry, $form) use ($job_id) {
            return false; // Disable redirect for job applications
        }, 10, 3);

        $job_post_id = $this->getJobPostId();

        if (!$job_post_id) {
            return;
            error_log('No job post ID found in submission. Skipping criteria evaluation.');
        }

        $criteria = Postmeta::getCriteriaForJob($job_post_id);

        if (empty($criteria)) {

            $this->setPassedCriteria(true);

            return;

        }

        error_log('Retrieved criteria for job post ID ' . $job_post_id . ': ' . print_r($criteria, true));

        $evaluation = $this->evaluateCriteria($criteria, $entry);

        $this->setPassedCriteria($evaluation);

    }

    /*
     * Recursive grouped rule evaluator
     *
     * Expected format:
     *
     * [
     *   'relation' => 'AND' | 'OR',
     *   'rules' => [...]
     * ]
     */
    private function evaluateCriteria(array $criteria) : bool {

        if (!isset($criteria['rules'])) {
            return true;
        }

        error_log('Evaluating criteria: ' . print_r($criteria, true));


        $relation = strtoupper($criteria['relation'] ?? 'AND');

        $results = [];

        foreach ($criteria['rules'] as $rule) {

            /*
             * Nested group
             */
            if (isset($rule['rules'])) {

                $results[] = $this->evaluateCriteria($rule);

                continue;

            }

            /*
             * Leaf rule
             */
            $field_id = $rule['field'] ?? null;

            if (!$field_id) {
                continue;
            }

            $value = rgpost(
                'input_' . str_replace('.', '_', $field_id)
            );

            error_log("Value: " . print_r($value, true));


            $results[] = $this->evaluateRule($value, $rule);

        }

        if ($relation === 'OR') {
            return in_array(true, $results, true);
        }

        return !in_array(false, $results, true);

    }

    /*
     * Single rule comparison engine
     */
    private function evaluateRule($value, array $rule) : bool {

        error_log('Evaluating rule: ' . print_r($rule, true) . ' with value: ' . print_r($value, true));

        if (!isset($rule['operator'], $rule['criteria'])) {
            return true;
        }

        $operator = $rule['operator'];
        $criteria = $rule['criteria'];

        $numeric_compare =
            is_numeric($value) &&
            is_numeric($criteria);

        if ($numeric_compare) {

            $value = (float)$value;
            $criteria = (float)$criteria;

        }

        switch ($operator) {

            case '>':
                return $value > $criteria;

            case '<':
                return $value < $criteria;

            case '>=':
                return $value >= $criteria;

            case '<=':
                return $value <= $criteria;

            case '=':
            case '==':
                return $value === $criteria;

            case '!=':
                return $value !== $criteria;

            case 'contains':
                return strpos((string)$value, (string)$criteria) !== false;

            case 'in':
                $log_value = in_array($value, (array)$criteria, true) ? 'true' : 'false';
                error_log("Evaluating 'in' operator: value '{$value}' is " . $log_value . " in criteria: " . print_r($criteria, true));
                return in_array($value, (array)$criteria, true);

            case 'between':

                if (!is_string($criteria)) {
                    return false;
                }
                
                $parts = array_map(
                    'trim',
                    explode(',', $criteria)
                );
            
                if (count($parts) !== 2) {
                    return false;
                }
            
                if (!is_numeric($parts[0]) || !is_numeric($parts[1])) {
                    return false;
                }
                
                $min = (float) $parts[0];
                $max = (float) $parts[1];
            
                if ($min > $max) {
                    return false;
                }
            
                if (!is_numeric($value)) {
                    return false;
                }
                
                $value = (float) $value;
            
                return ($value >= $min && $value <= $max);

            default:
                return true;

        }

    }

}