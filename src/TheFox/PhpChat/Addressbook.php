<?php

namespace TheFox\PhpChat;

use TheFox\Storage\YamlStorage;

class Addressbook extends YamlStorage{
	
	private $contactsId = 0;
	private $contacts = array();
	private $contactsByNodeId = array();
	
	public function __construct($filePath = null){
		parent::__construct($filePath);
		
		$this->data['timeCreated'] = time();
	}
	
	public function __sleep(){
		return array('contacts');
	}
	
	public function save(){
		$this->data['contacts'] = array();
		foreach($this->contacts as $contactId => $contact){
			
			$contactAr = array();
			$contactAr['nodeId'] = $contact->getNodeId();
			$contactAr['userNickname'] = $contact->getUserNickname();
			$contactAr['timeCreated'] = $contact->getTimeCreated();
			
			$this->data['contacts'][$contactId] = $contactAr;
		}
		
		$rv = parent::save();
		unset($this->data['contacts']);
		
		return $rv;
	}
	
	public function load(){
		if(parent::load()){
			
			if(isset($this->data['contacts']) && $this->data['contacts']){
				foreach($this->data['contacts'] as $contactId => $contactAr){
					#$this->contactsId++;
					$this->contactsId = (int)$contactId;
					
					$contact = new Contact();
					$contact->setId($this->contactsId);
					$contact->setNodeId($contactAr['nodeId']);
					$contact->setUserNickname($contactAr['userNickname']);
					$contact->setTimeCreated($contactAr['timeCreated']);
					
					$this->contacts[$contact->getId()] = $contact;
					$this->contactsByNodeId[$contact->getNodeId()] = $contact;
				}
			}
			unset($this->data['contacts']);
			
			return true;
		}
		
		return false;
	}
	
	public function contactAdd(Contact $contact){
		$ocontact = $this->contactGetByNodeId($contact->getNodeId());
		if(!$ocontact){
			$this->contactsId++;
			
			$contact->setId($this->contactsId);
			
			$this->contacts[$contact->getId()] = $contact;
			$this->contactsByNodeId[$contact->getNodeId()] = $contact;
			
			$this->setDataChanged(true);
		}
	}
	
	public function contactGetByNodeId($nodeId){
		if(isset($this->contactsByNodeId[$nodeId])){
			return $this->contactsByNodeId[$nodeId];
		}
		
		return null;
	}
	
	public function contactsGetByNick($userNickname){
		$contacts = array();
		foreach($this->contacts as $contactId => $contact){
			if(strtolower($contact->getUserNickname()) == strtolower($userNickname)){
				$contacts[] = $contact;
			}
		}
		return $contacts;
	}
	
	public function contactRemove($id){
		$rv = false;
		
		if(isset($this->contacts[$id])){
			$contact = $this->contacts[$id];
			if($contact){
				unset($this->contactsByNodeId[$contact->getNodeId()]);
				unset($this->contacts[$contact->getId()]);
				
				$this->setDataChanged(true);
				
				$rv = true;
			}
		}
		
		return $rv;
	}
	
	public function getContacts(){
		return $this->contacts;
	}
	
}
