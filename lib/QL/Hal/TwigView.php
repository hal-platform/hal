<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal;

use Twig_Environment;
use Slim\View as BaseView;

/**
 *  Extension of Slim view for Twig templates
 *
 *  @author Matt Colf <matthewcolf@quickenloans.com>
 */
class TwigView extends BaseView
{
    protected $env;

    /**
     *  Construct
     *
     *  @param Twig_Environment $env
     */
    public function __construct(Twig_Environment $env)
    {
        parent::__construct();
        $this->env = $env;
    }

    /**
     *  Render a template using Twig
     *
     *  @param string $template
     *  @param array $data
     *  @return string
     */
    public function render($template, $data = array())
    {
        return $this->env->loadTemplate($template)->render($this->all(), $data);
    }
}
