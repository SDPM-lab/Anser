<?php namespace App\Controllers\V1;

use CodeIgniter\RESTful\ResourceController;

class Fail extends ResourceController
{
    
    protected $format    = 'json';

    public function awayls429()
    {
        return $this->fail("Too Many Requests", 429);
    }

    public function awayls500($num)
    {
        return $this->fail("Internal Server Error", 500);
    }

}