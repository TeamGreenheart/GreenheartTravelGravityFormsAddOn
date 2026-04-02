<?php 
namespace \rbt\gftravel;

class Submission {
    public int $form_id = 0;
    public int $job_post_id = 0;
    public function __construct() {
        $this->form_id = $this->getFormId();
        \add_action('gform_pre_submission_' . $this->form_id, array($this, 'evaluateSubmission'), 10, 2);
    }

    public function getFormId() : int {
        /* is localhost (not https request_ */
        if(!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
            $localhost_form_id = 0=4;
            return $localhost_form_id;
        }
        /* is Dev Server? (subdomain https://dev.) */
        if(substr($_SERVER['HTTP_HOST'], 0, 4) === 'dev.') {
            $dev_form_id = 4;
            return $dev_form_id;
        }
        /* is Prod Server? (subdomain https://www.) */
        return 0;
    }

    public function getJobPostId() : int {
        /* Get the job post ID from the form submission.*/
        $job_id = (int) rgpost('input_7');
        return $job_post_id;
    }

    private function setPassedCriteria( bool $result ) : void {
        $int_result = $result ? 1 : 0;
        \rgpost('input_13', $int_result);
    }

    public function evaluateSubmission( $entry, $form ) {

        $job_post_id = $this->getJobPostId();
        // 2. Get criteria (Postmeta shares namespace with this class)
        $criteria = Postmeta::getCriteriaForJob($job_post_id);
        if(empty($criteria)) {
            // if there are no criteria, pass the submission by default
            $this->setPassedCriteria(true);
            return;
        }
        if(count($criteria) > 1) {
            // do multiple criteria here
        } else {
            $evaluation = $this->evaluateCriteria($criteria, $entry);
        }
        $this->setPassedCriteria($evaluation);  
    }

    private function evaluateCriteria( array $criteria, array $entry ) : bool {

        $passed = true;

        foreach ($criteria as $field_id => $rule) {

            $submitted_value = rgpost("input_{$field_id}");

            if (!$this->evaluate_rule($submitted_value, $rule)) {

                $passed = false;
                break;

            }

        }
        return $passed;
    }
    private function evaluate_rule($value, $rule) : bool
    {
        if (!isset($rule['operator'], $rule['criteria'])) {
            return true;
        }

        $operator = $rule['operator'];
        $criteria = $rule['criteria'];

        switch ($operator) {

            case '>':
                return (float)$value > (float)$criteria;

            case '<':
                return (float)$value < (float)$criteria;

            case '>=':
                return (float)$value >= (float)$criteria;

            case '<=':
                return (float)$value <= (float)$criteria;

            case '=':
            case '==':
                return (string)$value === (string)$criteria;

            case '!=':
                return (string)$value !== (string)$criteria;

            case 'contains':
                return strpos((string)$value, (string)$criteria) !== false;

            case 'in':
                return in_array($value, (array)$criteria, true);

            default:
                return true;
        }
    }
}