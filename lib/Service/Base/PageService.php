<?php
declare(strict_types=1);

namespace TwoQuick\Api\Service\Base;

use TwoQuick\Api\Entity\SeoInfo;

abstract class PageService extends Service implements SeoServiceInterface
{

    /**
     * @param SeoInfo $seoInfo
     * @return void
     */
    public function addSeoData(SeoInfo $seoInfo) : void
    {
        $this->data['seo'] = $seoInfo;
    }

}
