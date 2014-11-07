<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal;

use Twig_Template;

/**
 * @todo This needs to be removed and replaced by lazytwig
 */
class Layout
{
    /**
     *  Render a template with data
     *
     *  @param Twig_Template $template
     *  @param array $data
     *  @return string
     */
    public function render(Twig_Template $template, array $data = [])
    {
        return $template->render($data);
    }
}
