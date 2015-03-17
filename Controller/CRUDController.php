<?php

/*
 * This file is part of the YepsuaRADBundle.
 *
 * (c) Omar Yepez <omar.yepez@yepsua.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Yepsua\RADBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Yepsua\RADBundle\Controller\Controller;
use Yepsua\CommonsBundle\Persistence\Dao;
use Yepsua\RADBundle\UI\Grid;

use \YsJQuery as JQuery;
use \YsJQueryConstant as JQueryConstant;
use \YsGridResponse as GridResponse;

abstract class CRUDController extends Controller
{
    const NEW_VIEW = 'undefined';
    const SHOW_VIEW = 'undefined';
    
    const EDIT_VIEW = 'undefined';
    const TRANSLATOR_DOMAIN = null;
    
    const ENTITY_NAME = 'undefined';
    
    const GRID_PARAM_NAME_ROWS = 'rows';
    const GRID_PARAM_NAME_SIDX = 'sidx';
    const GRID_PARAM_NAME_PAGE = 'page';
    const GRID_PARAM_NAME_SORD = 'sord';
    const GRID_PARAM_NAME_FILTERS = 'filters';
    const ORDER_BY = 'ASC';
    
    const ERROR_HTTP_STATUS_CODE = 203;
    
    protected function crudIndex(Request $request = null){
        $grid = $this->gridSettings();
        return $this->commonsResponseVars(compact('grid'));
    }
    
    protected function commonsResponseVars($vars = array()){
      $translator_domain = static::TRANSLATOR_DOMAIN;
      $entity_name = static::ENTITY_NAME;
      return array_merge($vars, compact('translator_domain','entity_name'));
    }
    /**
     * Creates a new entity.
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return type
     */
    protected function crudCreate(Request $request = null)
    {
        try{
            
            $entity = $this->getEntity();
            $form = $this->createAndBindForm($request, $this->getEntityType() , $entity);
            $page = static::NEW_VIEW;
            
            if ($form->isValid()) {
                $this->saveEntity($entity);                
                if($request->get('_loop_create')){
                    $form = $this->createForm($this->getEntityType(), $this->getEntity());
                }else{
                    $page = static::SHOW_VIEW;
                    $form = $this->createDeleteForm($entity->getId());
                }
                $form = $form->createView();
                return $this->render($page, $this->commonsResponseVars(compact('entity','form')));
            }
            $form = $form->createView();
            return $this->success($page, $this->commonsResponseVars(compact('entity','form')));
            
        }catch(\Exception $e){
          return $this->failure($e);
        }
    }
    
    /**
     * Displays a form to create a new entity.
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return type
     */
    public function crudNew(Request $request = null)
    {
        try{
            $entity = $this->getEntity();
            $form   = $this->createForm($this->getEntityType(), $entity)
                           ->createView();
            
            return $this->commonsResponseVars(compact('entity','form'));
        }catch(\Exception $e){
            return $this->failure($e);
        }
    }
    
    /**
     * Finds and displays an entity.
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param type $id
     * @return type
     */
    public function crudShow(Request $request, $id)
    {
        try{

          $entity = $this->throwEntityNotFoundException($this->getRepository()->find($id));
          $form = $this->createDeleteForm($id)->createView();
          
          return $this->commonsResponseVars(compact('entity','form'));
        }catch(\Exception $e){
           return $this->failure($e);
        }
    }
    
    /**
     * Displays a form to edit an existing entity.
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param type $id
     * @return type
     */
    public function crudEdit(Request $request, $id)
    {
        try{
            $entity = $this->throwEntityNotFoundException($this->getRepository()->find($id));
            $form = $this->createForm( $this->getEntityType() , $entity)->createView();
            $delete_form = $this->createDeleteForm($id)->createView();
            
            return $this->commonsResponseVars(compact('entity','form','delete_form'));
            
        }catch(\Exception $e){
          return $this->failure($e);
        }
    }
    
    /**
     * Edits an existing entity.
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param type $id
     * @return type
     */
    public function crudUpdate(Request $request, $id)
    {
        try{
            $entity = $this->throwEntityNotFoundException($this->getRepository()->find($id));

            $form = $this->createForm($this->getEntityType(), $entity);
            $form->submit($request);
            
            $page = static::EDIT_VIEW;
            
            if ($form->isValid()) {
                $page = static::SHOW_VIEW;
                $this->saveEntity($entity);
                $form = $this->createDeleteForm($id)->createView();
                return $this->render($page, $this->commonsResponseVars(compact('entity','form')));
            }
            
            $form = $form->createView();
            $delete_form = $this->createDeleteForm($id)->createView();
            return $this->success($page, $this->commonsResponseVars(compact('entity','form','delete_form')));
            
        }catch(\Exception $e){
            return $this->failure($e);
        }
    }
    
    /**
     * Deletes an entity.
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param type $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function crudDelete(Request $request, $id)
    {
        try{
            $form = $this->createMultipleDeleteForm($request, $id);
            
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $ids = $this->getIdsToDelete($id);
                $entities = $this->getRepository()->findById($ids);

                foreach ($entities as $entity){
                    if($entity){
                      $em->remove($entity);
                    }
                }
                
                $em->flush();
            }
            return new Response();
        }catch(\Exception $e){
          return $this->failure($e);
        }
    }
    
    /**
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param type $entityName
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function searchJSONData(Request $request, $entityName, $getAll = false)
      {
          try{            
              JQuery::useComponent(JQueryConstant::COMPONENT_JQGRID);
              $getAll = ($getAll === true) || ($request->get(static::GRID_PARAM_NAME_ROWS, 0) <= 0) ? true : false;
              
              $orderBy = $request->get(static::GRID_PARAM_NAME_SIDX);
              
              $page = ($getAll) ? 1 : $request->get(static::GRID_PARAM_NAME_PAGE, 1);
              $rows = $request->get(static::GRID_PARAM_NAME_ROWS, 1);
              $sord = $request->get(static::GRID_PARAM_NAME_SORD, static::ORDER_BY);
              $filters = $request->get(static::GRID_PARAM_NAME_FILTERS, null);
              $response = new GridResponse();

              $repository = $this->getRepository();
              $count = Dao::count($repository);

              if($count > 0){
                  $query = Dao::buildQuery($repository, $entityName, $orderBy, $sord, $filters);
                  if(!$getAll){
                    $query->setMaxResults($rows)->setFirstResult(($page - 1) * $rows);
                  }
                  $entities = $query->getQuery()->getResult();

                  foreach ($entities as $entitie){
                      $response->addGridRow($this->dataRowMapping($entitie));
                  }
              }

              $totalRows = ($getAll) ? $page : $count / $rows;
              $totalRows = is_real($totalRows) ? intval($totalRows) + 1 : intval($totalRows);
              $response->setTotal($totalRows);
              $response->setPage($page);
              $response->setRecords($count);

              return new Response($response->buildResponseAsJSON());
          }catch(\Exception $e){
              return $this->failure($e);
          }
    }
    
    protected function getGrid(){
      $grid = new Grid(static::ENTITY_NAME, 'list.view.grid.title');
      $grid->setUrl($this->generateUrl(sprintf('%1$s_data', static::ENTITY_NAME)));
      $grid->setTranslator($this->get('translator'), static::TRANSLATOR_DOMAIN);
      $grid->setSortOrder(static::ORDER_BY);
      $grid->createView();
      return $grid;
    }
        
    /**
     * 
     */
    protected abstract function gridSettings();
    
    /**
     * 
     */
    protected abstract function dataRowMapping($entitie);
    
    /**
     * 
     */
    protected abstract function getEntity();
    
    /**
     * 
     * @return \Symfony\Component\Form\AbstractType;
     */
    protected abstract function getEntityType();
}
