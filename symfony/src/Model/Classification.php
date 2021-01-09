<?php

namespace App\Model;

class Classification
{
    public $invalid       = [];
    public $disabled      = [];
    public $inaccessible  = [];
    public $phoneLandline = [];
    public $phoneMissing  = [];
    public $phoneOptout   = [];
    public $emailMissing  = [];
    public $emailOptout   = [];
    public $reachable     = [];
}
