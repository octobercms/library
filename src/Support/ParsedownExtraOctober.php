<?php
namespace October\Rain\Support;

use ParsedownExtra;

/**
 * Markdown content parser, extension for October CMS
 *
 * @package october\support
 * @author Michał Łukaszewski
 */

class ParsedownExtraOctober extends ParsedownExtra
{
    protected function identifyLink($Excerpt)
    {
        $Span = parent::identifyLink($Excerpt);
        $octoberSpan = $this->makeImgResponsive($Span);

        return $octoberSpan;
    }

    protected function makeImgResponsive($Span)
    {
        if (empty($Span)){
           return;
        }
        if ($Span['element']['name'] !== 'img' || $Span['element']['attributes']['src'] === 'image') {
            return;
        }
        if (empty($Span['element']['attributes']['class'])){
            $Span['element']['attributes']['class'] = '';
        }
        if ($Span['element']['attributes']['class'] != 'img-responsive'){
            $Span['element']['attributes']['class'] .= ' img-responsive';
        }
        return $Span;
    }
}

