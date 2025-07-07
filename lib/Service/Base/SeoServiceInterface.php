<?php
declare(strict_types=1);


namespace TwoQuick\Api\Service\Base;

use TwoQuick\Api\Entity\SeoInfo;

interface SeoServiceInterface {
    public function addSeoData(SeoInfo $seoInfo);
}
