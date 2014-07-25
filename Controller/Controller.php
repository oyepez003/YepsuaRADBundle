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
use Symfony\Component\HttpFoundation\Response;

use Doctrine\DBAL\DBALException;

use Yepsua\SmarTwigBundle\UI\Message\Notification;

class Controller extends BaseController
{
    const REPOSITORY_NAMESPACE = 'undefined';
    
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
    public function throwNotFoundException($entity){
      if (!$entity) {
        throw $this->createNotFoundException('msg.unable.to.find.entity');
      }
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
    protected function exceptionManagerNotification(\Exception $ex){
      $response = null;
      try{
        throw $ex;
      }
      catch(NotFoundHttpException $nfhttpEx){
          $this->get('logger')->error($nfhttpEx->getMessage());
          $response = $this->notifyError($nfhttpEx->getMessage());
      }catch(DBALException $dbalEx){
          $this->get('logger')->error($dbalEx->getMessage());
          $response = $this->notifyError($dbalEx->getPrevious()->getMessage());
      }catch(\Exception $e){
          $this->get('logger')->crit($e->getMessage());
          $response = $this->notifyError($e->getMessage(), 403);
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
}
