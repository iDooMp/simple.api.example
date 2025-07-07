<?php

declare(strict_types=1);

namespace TwoQuick\Api\Service;

use Bitrix\Main\Application;
use TwoQuick\Api\Service\Base\Service;

class FavService extends Service
{

    /**
     * @var array
     */
    private array $result;
    /**
     * @var string
     */
    private string $sessionName = 'favorites';

    /**
     * @return string
     */
    public function getSessionName(): string
    {
        return $this->sessionName;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->result;
    }

    public function getTotalCount(): int{
        $session = Application::getInstance()->getSession();
        $result = 0;
        if($session->has($this->sessionName)){
            foreach ($session[$this->sessionName] as $iblock){
                $result += count($iblock);
            }
        }
        return $result;
    }

    /**
     * @param int $id
     *
     * @return void
     * @throws \Bitrix\Main\LoaderException
     */
    public function set(int $id = 0 )
    {
        if ($id <= 0) {
            throw new \Exception('Bad product ID');
        }
        $arProduct = HelperService::getProductWithCache($id);
        $iblockId = $arProduct['IBLOCK_ID'];
        $session = Application::getInstance()->getSession();
        
        if ($session->has($this->sessionName)) {
            $existFav = $session[$this->sessionName][$iblockId];
            if (!empty($existFav) && in_array($id, $existFav)) {
                $status = 'remove';
                $existFav = array_filter($existFav, function ($favId) use ($id) {
                    return $favId != $id;
                });
            } else {
                $existFav[] = $id;
                $status = 'add';
            }
            $session[$this->sessionName][$iblockId] = $existFav;
            $session->set($this->sessionName, $session[$this->sessionName]);
        } else {
            $existFav = [$iblockId => [$id]];
            $session->set($this->sessionName, $existFav);
            $status = 'add';
        }
        $this->result = [
            'status' => $status,
            'count' => $this->getTotalCount(),
            'product' => [
                'name' => $arProduct['NAME'],
                'picture' => $arProduct['PREVIEW_PICTURE']['SRC'],
            ]
        ];
    }

    /**
     * @return array
     */
    public function getFavSession(): array
    {
        $session = Application::getInstance()->getSession();
        return $session[$this->sessionName] ?? [];
    }

    /**
     * @return void
     */
    public function clear(){
        $session = Application::getInstance()->getSession();
        if ($session->has($this->sessionName)) {
            $session->set($this->sessionName, []);
        }
        $this->result = [
            'status' => 'cleared',
            'result' => 'Очищено!'
        ];
    }
}
