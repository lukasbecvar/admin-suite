<?php

namespace App\Twig;

use App\Util\AppUtil;
use Twig\TwigFunction;
use Twig\Extension\AbstractExtension;

/**
 * Class AppUtilExtension
 *
 * Extension for providing AppUtil methods
 *
 * @package App\Twig
 */
class AppUtilExtension extends AbstractExtension
{
    private AppUtil $appUtil;

    public function __construct(AppUtil $appUtil)
    {
        $this->appUtil = $appUtil;
    }

    /**
     * Get twig functions from AppUtil
     *
     * isFeatureFlagDisabled = isFeatureFlagDisabled in AppUtil
     *
     * @return TwigFunction[] Array of TwigFunction objects
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('isFeatureFlagDisabled', [$this->appUtil, 'isFeatureFlagDisabled'])
        ];
    }
}
