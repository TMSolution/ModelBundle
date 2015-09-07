<?php

namespace Core\ModelBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityNotFoundException;

class Model
{

 

    protected $container = null;
    protected $manager = null;
    protected $entityClass = null;
    protected $metadata = null;
    protected $fields = array();
    protected $association = array
        (
        1 => "OneToOne",
        2 => "ManyToOne",
        4 => "OneToMany",
        8 => "ManyToMany",
        3 => "ToOne",
        12 => "ToMany"
    );

    public function __construct($container, $entityName, $metadata = null, $managerName = null)
    {

        $this->container = $container;
        $this->entityClass = $entityName;
        $this->metadata = $metadata;

        $this->manager = $this->container->get('doctrine')->getManager($managerName);
    }

    public function getMetadata()
    {

        return $this->manager->getClassMetadata($this->getEntityClass());
    }

    public function getEntity()
    {
        return new $this->entityClass;
    }

    public function getEntityClass()
    {
        return $this->entityClass;
    }

    public function getManager()
    {
        return $this->manager;
    }

    protected function doSave($entityObject)
    {
        $this->manager->persist($entityObject);
    }

    protected function doRemove($entityObject)
    {
        $this->manager->remove($entityObject);
    }

    protected function executeImmediately($executeImmediately = false)
    {

        if ($executeImmediately === true) {
            $this->manager->flush();
        }
    }

    /*
     * @TODO co Mariusz miał na myśli ?
     */

    public function flush()
    {
        $this->manager->flush();
    }

    public function create($entityObject, $executeImmediately = false, $logOperation = false)
    {
        
        $this->doSave($entityObject);
        $this->executeImmediately($executeImmediately);
        return $entityObject;
    }

    public function createEntities(ArrayCollection $arrayCollection, $executeImmediately = false)
    {
        foreach ($arrayCollection as $entityObject) {
            $this->doSave($entityObject);
        }

        $this->executeImmediately($executeImmediately);
        return $arrayCollection;
    }

    /**
     * @todo Sprawdzic checkright
     */
    public function delete($entityObject, $executeImmediately = false, $logOperation = false)
    {
        $this->doRemove($entityObject);
        $this->executeImmediately($executeImmediately);
        
    }

    /**
     * Metoda sprawdza i przygotowywuje do aktualizacji obietku
     * @author Łukasz Wawrzyniak <lukasz.wawrzyniak@tmsolution.pl>
     * @param type $entityObject
     * @param type $executeImmediately
     * @return entityObject
     */
    public function update($entityObject, $executeImmediately = false, $logOperation = false)
    {
        
        $this->doSave($entityObject);
        $this->executeImmediately($executeImmediately);
        
        return $entityObject;
    }

    /**
     * Zwraca encję o wskazanym identyfikatorze.
     * 
     * @param mixed $id Identyfikator encji
     * @throws EntityNotFoundException Encja z wybranym id nie istnieje lub
     * niepoprawny typ id
     */
    public function findOneById($id)
    {

        $repository = $this->manager->getRepository($this->entityClass);
        $entityObject = $repository->findOneById($id);
        if ($entityObject == null) {
            throw new EntityNotFoundException(
            "Entity '{$this->entityClass}' with {$id} not found");
        }
        return $entityObject;
    }

    public function findOneBy($array)
    {

        $repository = $this->manager->getRepository($this->entityClass);
        $entityObject = $repository->findOneBy($array);
        if ($entityObject == null) {
            throw new EntityNotFoundException(
            "Entity '{$this->entityClass}' not found");
        }
        return $entityObject;
    }

    public function hasOneById($id)
    {
        return $this->getReference($id) != null;
    }

    public function hasOneBy(array $criteria)
    {
        return $this->getRepository()->findOneBy($criteria) != null;
    }

    public function getReference($id, $entity = null)
    {
        return $this->manager->getReference($entity ? $entity : $this->entityClass, $id);
    }

    public function findBy($array)
    {

        $repository = $this->manager->getRepository($this->entityClass);
        $entityObjects = $repository->findBy($array);
        return $entityObjects;
    }

    public function getRepository($entity = null)
    {
        return $this->manager->getRepository($entity ? $entity : $this->entityClass);
    }

    public function findAll()
    {
        return $this->manager->getRepository($this->entityClass)->findAll();
    }

    /*
     * order: array('field'=>'asc','field'=>'asc'...)
     * params: array('field'=>'value','field'=>'value'...)
     * 
     * @TODO Do poprawy 
     */

    public function read(array $params = array(), $limit = null, $offset = null, array $order = array())
    {
        
        return $this->manager->getRepository($this->entityClass)->findBy($params, $order, $limit, $offset);
    }

    public function getJson(array $params = array(), $limit = null, $offset = null, array $order = array())
    {

        return json_encode($this->read($this->entityClass, $params, $limit, $offset, $order, 'json'));
    }

    /**
     * Zwraca obiekt QueryBuilder.
     * 
     * @author Krzysiek Piasecki <krzysiekpiasecki@gmail.com>
     * @return \Doctrine\ORM\QueryBuilder QueryBuilder
     */
    public function getQueryBuilder($alias = 'u')
    {
        return $this->getRepository()->createQueryBuilder($alias);
    }

    /**
     * Zwraca fabrykę modeli.
     * 
     * @author Krzysiek Piasecki <krzysiekpiasecki@gmail.com>
     * @return ModelFactory Fabryka modeli.
     */
    public function getModelFactory()
    {

        return $this->container->get('model_factory');
    }

    /*
     * @TODO Rozbic na dwie mietody Jedna zwraca booleana druga wartosc
     */

    public function hasKey($key, $array)
    {

        if (isset($array[$key])) {
            return $array[$key];
        }
        return false;
    }

    public function getProperties()
    {

        $properties = [];
        $reflectionClass = $this->metadata->getReflectionClass();
        foreach ($reflectionClass->getProperties() as $property) {
            if ($this->checkProperty($property)) {
                $properties[] = $property->getName();
            }
        }
        return $properties;
    }

    public function getTargetEntity($type)
    {
        $associationMappings = $this->getMetadata()->getAssociationMappings();
        if (array_key_exists($type, $associationMappings)) {
            return $associationMappings[$type]['targetEntity'];
        }
    }

    /**
     * 
     * @param type $propertyName
     * @return type
     */
    public function checkPropertyByName($propertyName)
    {

        $reflectionClass = $this->metadata->getReflectionClass();
        if ($reflectionClass->hasProperty($propertyName)) {
            $property = $reflectionClass->getProperty($propertyName);
            return $this->checkProperty($property);
        } else {
            return false;
        }
    }

    public function checkMethod($entity, $fieldName)
    {
        $reflClass = new \ReflectionClass($entity);

        if ($reflClass->hasMethod('get' . $fieldName)) {
            return 'get' . $fieldName;
        } elseif ($reflClass->hasMethod('is' . $fieldName)) {
            return 'is' . $fieldName;
        } elseif ($reflClass->hasMethod('has' . $fieldName)) {
            return 'has' . $fieldName;
        }

        return false;
    }

    /**
     * 
     * @param type $propertyName
     * @return type
     * @throws Exception
     */
    public function checkMethodPrefix($propertyName)
    {
        $reflectionClass = $this->metadata->getReflectionClass();
        $property = $reflectionClass->getProperty($propertyName);
        $camelizePropertyName = $this->camelize($propertyName);

        $method = $this->checkMethodByPrefix($propertyName, 'add');

        if (empty($method)) {
            $method = $this->checkMethodByPrefix($propertyName, 'set');
        }

        if (!empty($method)) {
            return $method;
        }
        throw new Exception('Brak metody set lub add dla właściwości ' . $propertyName . '.');
    }

    /**
     * 
     * @param type $propertyName
     * @param type $prefix
     * @return string
     * @throws Exception
     */
    public function checkMethodByPrefix($propertyName, $prefix)
    {
        $reflectionClass = $this->metadata->getReflectionClass();
        $property = $reflectionClass->getProperty($propertyName);
        $camelizePropertyName = $this->camelize($propertyName);

        $setterName = strtolower($prefix) . $camelizePropertyName;


        if ($reflClass->hasMethod($setterName) && $reflClass->getMethod($setterName)->isPublic()) {
            return $setterName;
        }
        throw new Exception('Brak metody ' . $prefix . ' dla właściwości ' . $propertyName . '.');
    }

    /**
     * 
     * @param ReflectionProperty $property
     * @return boolean
     */
    private function checkProperty(\ReflectionProperty $property)
    {

        $propertyName = $property->getName();
        $camelProp = $this->camelize($propertyName);
        $reflClass = $property->getDeclaringClass();

        $getter = 'get' . $camelProp;
        $setter = 'set' . $camelProp;
        $isser = 'is' . $camelProp;
        $hasser = 'has' . $camelProp;

        if ($reflClass->hasMethod($getter) && $reflClass->getMethod($getter)->isPublic() && $reflClass->hasMethod($setter) && $reflClass->getMethod($setter)->isPublic()) {
            return true;
        } elseif ($reflClass->hasMethod($isser) && $reflClass->getMethod($isser)->isPublic()) {
            return true;
        } elseif ($reflClass->hasMethod($hasser) && $reflClass->getMethod($hasser)->isPublic()) {
            return true;
        } elseif ($reflClass->hasMethod('__get') && $reflClass->getMethod('__get')->isPublic()) {
            return true;
        } elseif ($reflClass->getProperty($propertyName)->isPublic()) {
            return true;
        } elseif (property_exists($this->getEntity(), $propertyName)) {
            return true;
        } /*
         * magicCall to boolean wynikający niewiadomo z czego
         * elseif ($this->magicCall && $reflClass->hasMethod('__call') && $reflClass->getMethod('__call')->isPublic()) {
          return true;
          } */
        return false;
    }

    /**
     * Get localized entity name.
     * 
     * @param int $index Choose one of entity names
     * @return string Localized entity name
     */
    public function getEntityName($index = 0, $entityName = null)
    {
        return $this->container->get('classmapperservice')->getEntityName($entityName ? $entityName : $this->entityClass, $index);
    }

    /**
     * 
     * @param string $string
     * @return string
     */
    private function camelize($string)
    {
        return preg_replace_callback('/(^|_|\.)+(.)/', function ($match) {
            return ('.' === $match[1] ? '_' : '') . strtoupper($match[2]);
        }, $string);
    }

    public function getRouteArray()
    {
        $masterRequest = $this->container->get('request_stack')->getMasterRequest();
        $routeArray = $this->container->get('router')->match($masterRequest->getPathInfo());
        return $routeArray;
    }

    /**
     * 
     * @param type $propertyName
     * @return type
     * @throws Exception
     */
    public function findSetterForPropertyByPrefix($propertyName, $methodPrefixes)
    {

        if (is_string($methodPrefixes)) {
            $methodPrefixes = array($methodPrefixes);
        }

        foreach ($methodPrefixes as $methodPrefix) {

            $method = $this->checktMethodExists($methodPrefix . ucfirst($propertyName));

            if ($method !== false) {
                return $method;
            }
        }
        return false;
    }

    public function checktMethodExists($methodName)
    {
        $reflectionClass = $this->metadata->getReflectionClass();
        if ($reflectionClass->hasMethod($methodName) && $reflectionClass->getMethod($methodName)->isPublic()) {
            return $methodName;
        }
        return false;
    }

    /**
     * Wyjatki z getClassMetadata i nazwy setterow
     */
    public function getFieldsInfo()
    {
        $metadata = $this->getMetadata();
        $fieldsInfo = array();
        foreach ($metadata->fieldMappings as $field => $parameters) {

            $fieldsInfo[$parameters["fieldName"]] = array("is_object" => false, "type" => $parameters["type"], "virtual" => false, "setter" => $this->findSetterForPropertyByPrefix($parameters["fieldName"], array("set", "add")));
        }

        foreach ($metadata->associationMappings as $field => $parameters) {

            $type = $this->association[$parameters["type"]];
            /* calculate add methods for associated fields - couses bugs in analize*/
            /*
            if ($type === "ManyToMany" || $type == "OneToMany") {

                
                $value = $this->findSingularForm($parameters["fieldName"]);
                if ($value !== false) {
                    $fieldsInfo[$value] = array("is_object" => true, "type" => "object", "association" => $type, "virtual" => true, "object_name" => $parameters["targetEntity"], "setter" => $this->findSetterForPropertyByPrefix($value, array("set", "add")), $value);
                }
            }
             
             */
            $fieldsInfo[$parameters["fieldName"]] = array("is_object" => true, "type" => "object", "association" => $type, "virtual" => false, "object_name" => $parameters["targetEntity"], "setter" => $this->findSetterForPropertyByPrefix($parameters["fieldName"], array("set", "add")));
        }
        return $fieldsInfo;
    }

    protected function findSingularForm($value)
    {
        if (mb_substr($value, mb_strlen($value) - 1) === "s") {
            return mb_substr($value, 0, mb_strlen($value) - 1);
        }
        return false;
    }

    /**
     * Convert arrayCollection with objects to array with object ids
     * @param \Doctrine\Common\Collections\ArrayCollection $arrayCollection
     * @return array
     */
    public function getIdsFromCollection($arrayCollection)
    {
        $ids = [];
        foreach ($arrayCollection AS $item) {
            $ids[] = $item->getId();
        }
        return $ids;
    }

    public function getUser()
    {
        return $this->container->get('security.context')->getToken()->getUser();
    }

 

}
