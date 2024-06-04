<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

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
