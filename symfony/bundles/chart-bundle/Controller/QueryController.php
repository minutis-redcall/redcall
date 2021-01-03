<?php

namespace Bundles\ChartBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_ADMIN")
 * @IsGranted("ROLE_DEVELOPER")
 */
class QueryController extends AbstractController
{


}