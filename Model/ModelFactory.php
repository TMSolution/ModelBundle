<?php

namespace Core\ModelBundle\Model;

class ModelFactory {

    const VIEW = "VIEW";
    const DELETE = "DELETE";
    const EDIT = "EDIT";
    const CREATE = "CREATE";

    protected $container = null;
    protected $manager = null;
    protected $modelList = array();
    protected $managerName=null;

    public function __construct($container,$managerName=null) {

        $this->container = $container;
        $this->managerName= $managerName;


        /*
         * @TODO Sprawdzić czy za każdym razem wytwarzana ujest noiwa instancja managera
         */
        $this->manager = $this->container->get('doctrine')->getManager($this->managerName);
    }

    public function getModel($entityName) {
        if (isset($this->modelList[$entityName])) {
            return $this->modelList[$entityName];
        }

        $model = $this->createModel($entityName);

        $this->modelList[$entityName] = $model;

        return $model;
    }

    protected function createModel($entityName) {
        
        $metadata = $this->manager->getClassMetadata($entityName);
        $modelName = str_replace('\\Entity\\', '\\Model\\', $metadata->name);

        if (class_exists($modelName) === false) {
            $model = new Model($this->container, $metadata->name,$metadata,$this->managerName);
            return $model;
        }
        $model = new $modelName($this->container, $metadata->name,$metadata,$this->managerName);
        return $model;
    }

}
