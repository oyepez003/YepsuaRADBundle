<?php

/*
 * This file is part of the YepsuaRADBundle.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Yepsua\RADBundle\ORM;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Doctrine\ORM\EntityRepository as BaseEntityRepository;

use Yepsua\CommonsBundle\Persistence\Dao as DAO;

/**
 * EntityRepository
 * @author Omar Yepez <omar.yepez@yepsua.com>
 */
class EntityRepository extends BaseEntityRepository{
    
    const QUERY_ENTITY_NAME = 'undefined';
    const REPOSITORY_NAMESPACE = 'undefined';
    
    protected function getLefJoinsAlias(){
        return array();
    }
    
    public function __construct($em, \Doctrine\ORM\Mapping\ClassMetadata $class) {
        parent::__construct($em, $class);
    }
    
    protected function buildQuery($withJoins = true){
      $query =  DAO::buildQuery($this,static::QUERY_ENTITY_NAME);
      if($withJoins){
        foreach ($this->getLefJoinsAlias() as $key => $value) {
          $query = $query->leftJoin($key, $value);
        }
      }
      return $query;
    }
    
    public function getCount(){
        return DAO::count($this);
    }
    
    public function isDataNotFound()
    {
        $cInstituo = $this->getCount();
        return $cInstituo <= 0;
    }
    
    /**
     * Gets the repository for an entity class.
     *
     * @param string $entityName The name of the entity.
     *
     * @return \Doctrine\ORM\EntityRepository The repository class.
     */
    protected function getRepository($repoId = null){
        if($repoId === null){
          $repo = $this->getRepository(static::REPOSITORY_NAMESPACE);
        }else{
          $repo = $this->getEntityManager()->getRepository($repoId);
        }
        return $repo;
    }
    
    /**
     * Returns a NotFoundHttpException.
     *
     * This will result in a 404 response code. Usage example:
     *
     *     throw $this->createNotFoundException('Page not found!');
     *
     * @param string    $message  A message
     * @param \Exception $previous The previous exception
     *
     * @return NotFoundHttpException
     */
    public function createNotFoundException($message = 'msg.unable.to.find.entit', \Exception $previous = null)
    {
        return new NotFoundHttpException($this->translate($message), $previous);
    }
}