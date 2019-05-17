<?php

namespace Newsletter2go\Controller\Api;


use Newsletter2go\Model\Field;

class DatatypeHelper
{
    public static function convertToN2gDatatype($datatype) :string
    {
        switch ($datatype) {
            case 'bool':
                $correctDataType = Field::DATATYPE_BOOLEAN;
                break;
            case 'float':
                $correctDataType = Field::DATATYPE_FLOAT;
                break;
            case 'int':
                $correctDataType = Field::DATATYPE_INTEGER;
                break;
            case 'datetime':
                $correctDataType = Field::DATATYPE_DATE;
                break;
            default:
                $correctDataType = Field::DATATYPE_STRING;
        }

        return $correctDataType;
    }
}
