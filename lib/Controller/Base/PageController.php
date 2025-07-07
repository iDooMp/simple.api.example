<?php
declare(strict_types=1);

namespace TwoQuick\Api\Controller\Base;

abstract class PageController extends Controller
{
    abstract public function actionIndex();
}