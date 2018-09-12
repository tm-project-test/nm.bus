<?php

namespace NM\Bus;

class ObjectSender
{
    private $elementId;
    private $elementFields;
    private $allElementsIds = [];
    private $allElementsIdsToTransfer = [];

    public function __construct($elementId)
    {
        $this->elementId = (int) $elementId;
        $this->allElementsIds[] = $this->elementId;
        $this->getElementFields();
    }

    public function send()
    {
        \NM\Bus::client()
            ->setObjectId($this->getIblockId())
            ->setSentData($this->getAllData())
            ->send();
    }

    private function getIblockId()
    {
        return $this->elementFields['IBLOCK_ID'];
    }

    private function getElementFields()
    {
        $res = \CIBlockElement::GetByID($this->elementId);
        $this->elementFields = $res->Fetch();
    }

    private function getAllData()
    {
        $result = [];

        while(!empty($this->allElementsIds)) {
            $result[] = $this->getElementData(array_shift($this->allElementsIds));
        }

        return array_reverse($result);
    }

    private function getElementData($elementId)
    {
        $res = \CIBlockElement::GetByID($elementId);

        if($result = $res->Fetch()){
            $result['PROPERTY_VALUES'] = [];

            $props = [];
            $resProp = \CIBlockProperty::GetList([], ['IBLOCK_ID' => $result['IBLOCK_ID']]);
            while($arProp = $resProp->GetNext()){
                $props[] = $arProp['CODE'];
            }

            foreach($props as $prop){
                $resProp = \CIBlockElement::GetProperty($result['IBLOCK_ID'], $elementId, [], ['CODE' => $prop]);
                if($arProp = $resProp->Fetch()){
                    if(
                        $arProp['PROPERTY_TYPE'] === 'E' &&
                        $arProp['VALUE'] &&
                        !in_array($arProp['VALUE'], $this->allElementsIdsToTransfer)
                    ){
                        $this->allElementsIds[] = $arProp['VALUE'];
                        $this->allElementsIdsToTransfer[] = $arProp['VALUE'];
                    }

                    $result['PROPERTY_VALUES'][$arProp['CODE']] = $arProp['VALUE'];
                }
            }
        }

        return $result;
    }
}