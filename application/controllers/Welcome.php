<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Welcome Controller
 * 
 * Default controller that redirects to appropriate landing page
 * For this restaurant system, we redirect customers to the landing page
 */
class Welcome extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Table_model');
    }

    /**
     * Index method - redirects to customer landing page
     */
    public function index()
    {
        // Redirect to customer landing page
        redirect('customer');
    }
}
