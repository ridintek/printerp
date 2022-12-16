<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * This Jobs controller is running by CRONJOB.
 */
class Jobs extends MY_Controller
{
  public function __construct()
  {
    parent::__construct();
  }

  /**
   * Run as CRONJOB.
   */
  public function index()
  {
    if (!is_cli()) die('This program must be run under command line.');

    echo "Jobs started.\r\n";

    if ($job = $this->site->getJob(['status' => 'pending'])) {
      if (empty($job->request)) {
        $this->site->updateJob($job->id, ['response' => 'Request is missing.', 'status' => 'failed']);
        die;
      }

      echo "Jobs process $job->request\r\n";
      $this->site->updateJob($job->id, ['status' => 'processing']);
      exec('php ' . FCPATH . "index.php {$job->request}", $res);
      $this->site->updateJob($job->id, ['response' => implode("\r\n", $res), 'status' => 'success']);
    } else {
      echo "No jobs.\r\n";
    }

    echo "Jobs finished.\r\n";
  }

  public function add($request)
  {
    $jobData = [
      'request' => $request,
      'status'  => 'pending'
    ];

    if ($this->site->addJob($jobData)) {
      $this->response(201, ['message' => 'Job has been created.']);
    }
    $this->response(400, ['message' => 'Failed to create job.']);
  }

  public function test()
  {
    $param = func_get_args();
    die(json_encode($param));
  }
}
