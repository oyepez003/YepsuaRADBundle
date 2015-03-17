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

use Symfony\Bundle\FrameworkBundle\Controller\Controller as BaseController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Form;

use Doctrine\DBAL\DBALException;

use Yepsua\SmarTwigBundle\UI\Message\Notification;

class Controller extends BaseController
{
    const REPOSITORY_NAMESPACE = 'undefined';
    
    const ERROR_HTTP_STATUS_CODE = 200;
    
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
    public function createNotFoundException($message = 'Not Found', \Exception $previous = null)
    {
        return new NotFoundHttpException($this->translate($message), $previous);
    }
  
    /**
     * Creates a form to delete an entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return Symfony\Component\Form\Form The form
     */
    protected function createDeleteForm($id, array $options = array())
    {
        return $this->createFormBuilder(array('id' => $id), $options)
            ->add('id', 'hidden')
            ->getForm()
        ;
    }
    
    /**
     * 
     * @return \Symfony\Component\Translation\Translator $translator 
     */
    protected function getTranslator(){
      return $this->get('translator');
    }
    
    /**
     * {@inheritdoc}
     *
     * @api
     */
    protected function translate($id, $parameters = array(), $domain = null , $locale = null){
      return $this->getTranslator()->trans($id, $parameters, $domain, $locale);
    }
    
    /**
     * This method is deprecated, please use the ::getEntityRepository() method.
     *
     * @param string $className
     *
     * @return \Doctrine\Common\Persistence\ObjectRepository
     * @deprecated
     */
    public function getRepository($className = null){
      if($className === null){
        $repo = $this->getRepository(static::REPOSITORY_NAMESPACE);
      }else{
        $repo = $this->getEntityManager()->getRepository($className);
      }
      return $repo;
    }
    
    /**
     * Gets the repository for a class.
     *
     * @param string $className
     *
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    public function getEntityRepository($className = null){
      if($className === null){
        $repo = $this->getRepository(static::REPOSITORY_NAMESPACE);
      }else{
        $repo = $this->getEntityManager()->getRepository($className);
      }
      return $repo;
    }
    
    /**
     * 
     * @param object $entity
     * @throws NotFoundHttpException
     */
    public function throwNotFoundException($entity = false){
      if (!$entity) {
        throw $this->createNotFoundException('msg.unable.to.find.entity');
      }
    }
    
    /**
     * 
     * @param object $entity
     * @throws NotFoundHttpException
     */
    public function throwEntityNotFoundException($entity = false){
      if (!$entity) {
        throw $this->createNotFoundException('msg.unable.to.find.entity');
      }
      return $entity;
    }
    
    /**
     * Returns the Doctrine Entity Manager
     * @return type
     */
    public function getEntityManager(){
      return $this->getDoctrine()->getManager();
    }
    
    /**
     * 
     * @param \Exception $ex
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    protected function exceptionManagerNotification(\Exception $ex, $status = 200, $headers = array()){
      $response = null;
      try{
        throw $ex;
      }
      catch(NotFoundHttpException $nfhttpEx){
          $this->get('logger')->error($nfhttpEx->getMessage());
          $response = $this->notifyError($nfhttpEx->getMessage(), $status, $headers);
      }catch(DBALException $dbalEx){
          $this->get('logger')->error($dbalEx->getMessage());
          $response = $this->notifyError($dbalEx->getPrevious()->getMessage(), $status, $headers);
      }catch(\Exception $e){
          $this->get('logger')->crit($e->getMessage());
          $response = $this->notifyError($e->getMessage(), 403, $headers);
      }
      return $response;
    }
    
    /**
     * 
     * @param type $message
     * @param type $level
     * @param type $status
     * @param type $headers
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function notification($message, $level = Notification::SAY_LEVEL, $status = 200, $headers = array()){
      return new Response(Notification::notify($level,$message), $status, $headers);
    }
    
    /**
     * 
     * @param type $message
     * @param type $status
     * @param type $headers
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function notify($message, $status = 200, $headers = array()){
      return $this->notification($message, Notification::SAY_LEVEL, $status, $headers);
    }
    
    /**
     * 
     * @param type $message
     * @param type $status
     * @param type $headers
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function notifyError($message, $status = 200, $headers = array()){
      return $this->notification($message, Notification::ERROR_LEVEL, $status, $headers);
    }
    
    /**
     * 
     * @param type $message
     * @param type $status
     * @param type $headers
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function notifyAlarm($message, $status = 200, $headers = array()){
      return $this->notification($message, Notification::ALARM_LEVEL, $status, $headers);
    }
    
    /**
     * 
     * @param type $message
     * @param type $status
     * @param type $headers
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function notifyAlert($message, $status = 200, $headers = array()){
      return $this->notification($message, Notification::ALERT_LEVEL, $status, $headers);
    }

    /**
     * 
     * @param type $message
     * @param type $status
     * @param type $headers
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function notifyNotice($message, $status = 200, $headers = array()){
      return $this->notification($message, Notification::NOTICE_LEVEL, $status, $headers);
    }
    
    /**
     * 
     * @param \Symfony\Component\Form\Form $form
     * @return type
     */
    protected function getFormErrors(Form $form){
      return $form->getErrors(true,true);
    }
    
    /**
     * 
     * @param \Symfony\Component\Form\Form $form
     * @return type
     */
    protected function throwFormException(Form $form){
      return $this->throwException($this->getFormErrors($form));
    }
        
    /**
     * 
     * @param type $message
     * @throws \Exception
     */
    protected function throwException($message){
      throw new \Exception($message);
    }
    
    /**
     * 
     * @param \Symfony\Component\Form\Form $form
     */
    protected function validateForm(Form $form){
      if(!$form->isValid()){
        $this->throwFormException($form);
      }
    }
    
    /**
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param type $type
     * @param type $data
     * @param array $options
     * @return type
     */
    protected function crateAndValidateForm(Request $request, $type, $data = null, array $options = array()){
      $form = $this->createForm($type, $data, $options);
      $form->submit($request);
      $this->validateForm($form);
      return $form;
    }
    
    /**
     * Creates, binds and returns a Form instance from the type of the form.
     *
     * @param string|FormTypeInterface $type    The built type of the form
     * @param mixed                    $data    The initial data for the form
     * @param array                    $options Options for the form
     *
     * @return Form
     */
    public function createAndBindForm(Request $request, $type, $data = null, array $options = array())
    {
      $form = $this->createForm($type, $data, $options);
      $form->submit($request);
      return $form;
    }
    
    
    /**
     * 
     * @param type $entity
     */
    protected function saveEntity($entity){
      $em = $this->getDoctrine()->getManager();
      $em->persist($entity);
      $em->flush();
    }
    
    /**
     * 
     * @param type $e
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleException($e){
      self::getLogger()->crit($e->getMessage());
      return $this->exceptionManagerNotification($e, static::ERROR_HTTP_STATUS_CODE);
    }
    
    /**
     * 
     * @param type $e
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function failure($e){
      return $this->handleException($e);
    }
    
    /**
     * 
     * @return type
     */
    protected final function getLogger(){
      return $this->get('logger');
    }
    
    protected function createMultipleDeleteForm(Request $request, $id){
      if(strtoupper($request->getMethod()) == "DELETE"){
        $form = $this->createDeleteForm($id);
      }else{
        $form = $this->createDeleteForm($id,array('csrf_protection' => false));
      }
      $form->submit($request);
      return $form;
    }
    
    protected function getIdsToDelete($id){
       return strpos($id,',') ? explode(',', $id) : array($id);
    }
    
    protected function success($view, array $parameters = array(), Response $response = null){
      return $this->render($view,$parameters,new Response(null, 202));
    }
}
