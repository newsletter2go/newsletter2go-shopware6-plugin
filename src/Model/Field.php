<?php

namespace Newsletter2go\Model;


class Field
{
    const DATATYPE_INTEGER = 'integer';
    const DATATYPE_BOOLEAN = 'boolean';
    const DATATYPE_STRING = 'string';
    const DATATYPE_DATE = 'date';
    const DATATYPE_ARRAY = 'array';
    const DATATYPE_OBJECT = 'object';
    const DATATYPE_BINARY = 'binary';
    const DATATYPE_FLOAT = 'float';

    private $id;
    private $name;
    private $description;
    private $type;

    /**
     * Field constructor.
     * @param $id
     * @param string $type
     * @param null $name
     * @param null $description
     */
    public function __construct($id, $type = Field::DATATYPE_STRING, $name = null, $description = null)
    {
        $this->id = $id;
        $this->type = $type;
        if (is_null($name)) {
            $this->setName($id);
        }
        if (is_string($this->description)) {
            $this->description = $description;
        }
    }


    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name): void
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $name, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }

        $this->name = implode(' ', $ret);
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description): void
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type): void
    {
        $this->type = $type;
    }
}
