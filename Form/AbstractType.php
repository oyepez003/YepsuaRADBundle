<?php

/*
 * This file is part of the YepsuaRADBundle.
 *
 * (c) Omar Yepez <omar.yepez@yepsua.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Yepsua\RADBundle\Form;

use Symfony\Component\Form\AbstractType as BaseAbstractType;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
abstract class AbstractType extends BaseAbstractType
{
  protected $formType = 'undefined';
  const FORM_TYPE_NEW = 'NEW';
  const FORM_TYPE_EDIT = 'EDIT';
  const FORM_TYPE_DELETE = 'DELETE';
  
  public function setFormType($formType){
    $this->formType = $formType;
  }
  
  public function isFormTypeNew(){
    return $this->isFormType(static::FORM_TYPE_NEW);
  }
  
  public function isFormTypeEdit(){
    return $this->isFormType(static::FORM_TYPE_EDIT);
  }
  
  public function isFormTypeDelete(){
    return $this->isFormType(static::FORM_TYPE_DELETE);
  }
  
  public function isFormType($formType){
    return $this->formType === $formType;
  }
}
