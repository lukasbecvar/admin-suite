<?php

namespace App;

use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;

/**
 * Class Kernel
 *
 * The kernel for the application
 *
 * @package App
 */
class Kernel extends BaseKernel
{
    use MicroKernelTrait;
}
